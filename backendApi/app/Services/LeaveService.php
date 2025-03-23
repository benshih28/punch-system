<?php

namespace App\Services;

use App\Models\Leave;
use App\Models\LeaveType;
use App\Models\Employee;
use Illuminate\Support\Facades\Log;
use App\Services\LeaveResetService;
use Carbon\Carbon;

class LeaveService
{
    protected $leaveResetService;

    public function __construct(LeaveResetService $leaveResetService)
    {
        $this->leaveResetService = $leaveResetService;
    }

    const WORK_HOURS_PER_DAY = 8;  // 每天上班時數

    //  1. 申請請假
    // 根據前端送來的資料，算好請假時數，然後寫入資料庫
    public function applyLeave(array $data): Leave
    {
        $user = auth()->user();

        // 1️⃣ 先計算這次請假有幾小時
        $leaveTypeId = $data['leave_type_id'];
        $hours = $this->calculateHours($data['start_time'], $data['end_time']);

        // 2️⃣ 拿到這個假別的總時數
        $remainingHours = $this->getRemainingLeaveHours($leaveTypeId, $user->id, $data['start_time']);

        // 3️⃣ 判斷剩餘時數夠不夠
        if (!is_null($remainingHours) && $remainingHours < $hours) {
            throw new \Exception("剩餘時數不足，請重新修改請假區間", 400);
        }

        // 4️⃣ **建立請假單**
        $leave = Leave::create([
            'user_id' => $user->id,
            'leave_type_id' => $data['leave_type_id'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'leave_hours' => $hours,
            'reason' => $data['reason'] ?? '',
            'status' => $data['status'] ?? 0,
            'attachment' => isset($data['attachment']) ? $data['attachment'] : null, // **如果有附件才更新**
        ]);

        return $leave;
    }

    // 2. 查詢個人全部請假紀錄
    public function getLeaveList($user, array $filters)
    {
        $query = Leave::with(['user', 'file'])->where('user_id', $user->id);
        $this->applyFilters($query, $filters);

        return $query->select('leaves.*')
            ->orderByRaw('FIELD(status, 0, 1, 3, 2, 4)') // 依照 0 -> 1 -> 3 -> 2 -> 4 排序
            ->orderBy('created_at', 'asc') // 申請時間越早，排越前
            ->paginate(10);
    }

    // 3. 查詢「部門」請假紀錄（主管 & HR）
    public function getDepartmentLeaveList($user, array $filters)
    {
        $query = Leave::with(['user', 'file', 'employee']) // ✅ 同時載入 `user` 和 `file`
            ->whereHas('user.employee', fn($q) => $q->where('department_id', $user->employee->department_id));

        // ✅ 確保過濾條件生效
        $this->applyFilters($query, $filters);

        return $query->select('leaves.*')
            ->orderByRaw('FIELD(status, 0, 1, 3, 2, 4)') // 依照 0 -> 1 -> 3 -> 2 -> 4 排序
            ->orderBy('created_at', 'asc') // 申請時間越早，排越前
            ->paginate(10);
    }

    // 4. 查詢「全公司」請假紀錄（HR）
    public function getCompanyLeaveList(array $filters)
    {
        // Log::info('getCompanyLeaveList called with filters:', $filters);

        $query = Leave::with(['user', 'file']); // ✅ 同時載入 `user` 和 `file` 和 `employee`

        // ✅ 確保過濾條件生效
        $this->applyFilters($query, $filters);

        // 查詢所有請假單，分頁 10 筆
        $leaves = $query->select('*')
            ->orderByRaw('FIELD(status, 1, 0, 3, 2, 4)') // 指定狀態排序順序
            ->orderBy('created_at', 'asc') // 其次依據 start_time 排序
            ->paginate(10);

        Log::info('Query Result:', ['leaves' => $leaves->items()]);

        return $leaves;
    }

    // 5. 更新單筆紀錄
    public function updateLeave(Leave $leave, array $data, $user, $leaveStartTime): Leave
    {
        Log::info("📅 更新請假 - 傳遞 leaveStartTime", ['leaveStartTime' => $leaveStartTime]);

        // 1️⃣ **是否有修改請假時數**
        $isUpdatingHours = isset($data['start_time'], $data['end_time']);

        if ($isUpdatingHours) {
            $startTime = Carbon::parse($data['start_time']);
            $endTime = Carbon::parse($data['end_time']);

            if ($startTime->greaterThanOrEqualTo($endTime)) {
                throw new \Exception("請假結束時間必須大於開始時間", 400);
            }
        }

        // 2️⃣ **取得假別資訊**
        $leaveTypeId = $data['leave_type_id'] ?? $leave->leave_type_id;
        $leaveType = LeaveType::find($leaveTypeId);

        if (!$leaveType) {
            throw new \Exception("請假類型無效", 400);
        }

        // 3️⃣ **生理假檢查**
        if ($leaveType->name === 'Menstrual Leave' && $user->gender !== 'female') {
            throw new \Exception('您無法申請生理假', 403);
        }

        // 4️⃣ **計算新的請假時數**
        $hours = $isUpdatingHours
            ? $this->calculateHours($data['start_time'], $data['end_time'])
            : $leave->leave_hours;

        // 6️⃣ **檢查剩餘請假時數**
        if ($isUpdatingHours) {
            $remainingHours = $this->leaveResetService->getRemainingLeaveHours($leaveTypeId, $leave->user_id, $leaveStartTime, $leave->id);

            if ($remainingHours < $hours) {
                throw new \Exception("剩餘時數不足，請重新修改請假區間", 400);
            }
        }

        // 7️⃣ **更新 `leaves` 表**
        $leave->update([
            'leave_type_id' => $leaveTypeId,
            'start_time' => $data['start_time'] ?? $leave->start_time,
            'end_time' => $data['end_time'] ?? $leave->end_time,
            'leave_hours' => $hours,
            'reason' => $data['reason'] ?? $leave->reason,
            'status' => $data['status'] ?? $leave->status,
            'attachment' => isset($data['attachment']) ? $data['attachment'] : null,
        ]);

        return $leave->fresh();
    }

    // 5. 計算跨天請假時數 (支援單日、跨日)
    private function calculateHours(string $startTime, string $endTime): float
    {
        $start = strtotime($startTime);
        $end = strtotime($endTime);

        $startDate = date('Y-m-d', $start);
        $endDate = date('Y-m-d', $end);

        if ($startDate === $endDate) {
            // 同一天直接算時數
            return $this->calculateOneDayHours($startTime, $endTime);
        }

        // 跨天情況
        $firstDayHours = $this->calculateOneDayHours($startTime, "$startDate 18:00:00");
        $lastDayHours = $this->calculateOneDayHours("$endDate 09:00:00", $endTime);

        $middleDays = (strtotime($endDate) - strtotime($startDate)) / 86400 - 1;
        $middleDaysHours = max($middleDays, 0) * self::WORK_HOURS_PER_DAY;

        return round($firstDayHours + $lastDayHours + $middleDaysHours, 2);
    }

    // 6. 計算單天請假時數 (考慮上下班時間)
    private function calculateOneDayHours(string $start, string $end): float
    {
        $startTime = strtotime($start);
        $endTime = strtotime($end);

        // 如果時間不符合上班時間(可依公司規定調整)
        $workStart = strtotime(date('Y-m-d', $startTime) . ' 09:00:00');
        $workEnd = strtotime(date('Y-m-d', $startTime) . ' 18:00:00');

        // 限制只計算上班時段
        if ($startTime < $workStart) $startTime = $workStart;
        if ($endTime > $workEnd) $endTime = $workEnd;

        // 計算小時數 (包含中午休息時間可以加上去)
        $hours = ($endTime - $startTime) / 3600;

        // 例如：12:00-13:00是午休，這段不算工時
        $lunchStart = strtotime(date('Y-m-d', $startTime) . ' 12:00:00');
        $lunchEnd = strtotime(date('Y-m-d', $startTime) . ' 13:00:00');

        if ($startTime < $lunchEnd && $endTime > $lunchStart) {
            $hours -= 1;  // 扣掉午休1小時
        }

        return round($hours, 2);
    }

    // 7. 計算特殊假別剩餘小時數
    public function getRemainingLeaveHours($leaveTypeId, $userId, $leaveStartTime = null, $excludeLeaveId = null)
    {
        $leaveType = LeaveType::find($leaveTypeId);

        if (!$leaveType) {
            return null; // 假別不存在
        }

        // 針對特休和生理假使用專門的方法計算
        if ($leaveType->name === 'Annual Leave') {
            return $this->leaveResetService->getRemainingAnnualLeaveHours($userId, $leaveStartTime);
        } elseif ($leaveType->name === 'Menstrual Leave') {
            return $this->leaveResetService->getRemainingMenstrualLeaveHours($userId, $leaveStartTime, $excludeLeaveId);
        }

        // 其他假別使用通用計算方式
        return $this->leaveResetService->getRemainingLeaveHours($leaveTypeId, $userId);
    }

    // 8. 統一查詢結果及修改格式
    private function applyFilters($query, array $filters): void
    {
        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->where(function ($q) use ($filters) {
                $q->whereBetween('start_time', [$filters['start_date'] . ' 00:00:00', $filters['end_date'] . ' 23:59:59'])
                    ->orWhereBetween('end_time', [$filters['start_date'] . ' 00:00:00', $filters['end_date'] . ' 23:59:59'])
                    ->orWhere(function ($q) use ($filters) {
                        $q->where('start_time', '<=', $filters['start_date'] . ' 00:00:00')
                            ->where('end_time', '>=', $filters['end_date'] . ' 23:59:59');
                    });
            });

            if (!empty($filters['leave_type'])) {
                $query->whereHas('leaveType', function ($q) use ($filters) {
                    $q->where('id', $filters['leave_type']);
                });
            }

            if (isset($filters['status'])) { // 檢查 status 是否存在(防止0被empty過濾掉改使用isset)
                $query->where('status', $filters['status']);
            }

            if (!empty($filters['employee_id'])) {
                $query->where('user_id', $filters['employee_id']);
            }

            if (!empty($filters['department_id'])) {
                $query->whereHas('employee', function ($q) use ($filters) {
                    $q->where('department_id', $filters['department_id']);
                });
            }
        }
    }
}

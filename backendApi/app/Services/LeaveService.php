<?php

namespace App\Services;

use App\Models\Leave;
use App\Models\LeaveType;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Collection;


class LeaveService
{
    const WORK_HOURS_PER_DAY = 8;  // 每天上班時數

    //  1. 申請請假
    // 根據前端送來的資料，算好請假時數，然後寫入資料庫
    public function applyLeave(array $data): Leave
    {
        $user = auth()->user(); // 獲取當前用戶
        $leaveTypeId = $data['leave_type_id']; // 獲取假別類型
        $leaveHours = $data['leave_hours']; // 獲取請假小時數

        // 取得剩餘假別小時數
        $remainingHours = $this->getRemainingLeaveHours($leaveTypeId, $user->id);

        // 檢查剩餘小時數是否足夠
        if ($remainingHours < $leaveHours) {
            throw new \Exception('剩餘小時數不足', 400);
        }

        // 計算請假時間的總小時數
        $hours = $this->calculateHours($data['start_time'], $data['end_time']);

        // 處理附件上傳
        $attachmentPath = null;
        if (!empty($data['attachment']) && $data['attachment']->isValid()) {
            $file = $data['attachment'];

            // 取得副檔名
            $extension = $file->getClientOriginalExtension();

            // UUID檔名 + 副檔名
            $filename = Str::uuid() . '.' . $extension;

            // 存檔到backendAPI/storage/app/public/attachments
            $attachmentPath = $file->storeAs('attachments', $filename, 'public');
        }

        return Leave::create([
            'user_id' => $user->id, // 使用者 ID，避免從資料中傳入
            'leave_type_id' => $leaveTypeId, // 假別類型 ID
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'leave_hours' => $hours,   // 存小時數
            'reason' => $data['reason'] ?? '', // 默認為空字串
            'status' => 'pending', // 預設為待審核
            'attachment' => $attachmentPath, // 儲存附件路徑
        ]);
    }

    // 2. 查詢請假清單（帶角色權限）
    public function getLeaveList($user, array $filters): Collection
    {
        $query = Leave::with('user');

        // 員工只能查自己，主管查部門，HR查全部
        if ($user->role === 'employee') {
            $query->where('user_id', $user->id);
        } elseif ($user->role === 'manager') {
            $query->whereHas('user', fn($q) => $q->where('department_id', $user->department_id));
        } elseif ($user->role === 'hr') 
        $this->applyFilters($query, $filters);

        return $query->orderBy('start_time', 'desc')->get();
    }

    // 3. 查單筆（帶角色權限）
    public function getSingleLeave($user, int $id): ?Leave
    {
        $query = Leave::with('user')->where('id', $id);

        if ($user->role === 'employee') {
            // 員工只能查詢自己的假單
            $query->where('user_id', $user->id);
        } elseif ($user->role === 'manager') {
            // 主管可以查詢同部門員工的假單
            $query->whereHas('user', fn($q) => $q->where('department_id', $user->department_id));
        } elseif ($user->role === 'hr') {
            // HR可以查詢所有的假單
            // 這裡返回所有假單
        }

        return $query->first();
    }

    // 4. 更新單筆紀錄
    public function updateLeave(Leave $leave, array $data): Leave
    {
        // 計算請假小時數
        $hours = $this->calculateHours($data['start_time'], $data['end_time']);

        // 開始更新假單資料
        $leave->update([
            'leave_type_id' => $data['leave_type'],  // 更新假別
            'start_time' => $data['start_time'],     // 更新開始時間
            'end_time' => $data['end_time'],         // 更新結束時間
            'leave_hours' => $hours,                 // 計算並更新請假小時數
            'reason' => $data['reason'] ?? $leave->reason,  // 如果沒有傳入reason，則保留原來的
            'status' => $data['status'] ?? $leave->status,  // 如果沒有傳入status，則保留原來的
            'attachment' => $data['attachment'] ?? $leave->attachment,  // 更新附件路徑，若無則保留原來的
        ]);

        return $leave;
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
    public function getRemainingLeaveHours($leaveTypeId, $userId)
    {
        // 獲取該假別的總小時數
        $leaveType = LeaveType::find($leaveTypeId);
        $totalHours = $leaveType->total_hours;

        // 計算該使用者已請的總小時數
        $usedHours = Leave::where('user_id', $userId)
            ->where('leave_type_id', $leaveTypeId)
            ->sum('leave_hours');  // 聚合已請的總小時數

        // 計算剩餘小時數
        $remainingHours = $totalHours - $usedHours;

        return $remainingHours;
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
        }

        if (!empty($filters['leave_type'])) {
            $query->where('leave_type', $filters['leave_type']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
    }
}

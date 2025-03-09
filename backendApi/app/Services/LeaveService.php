<?php

namespace App\Services;

use App\Models\Leave;
use App\Models\LeaveType;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Collection;
use App\Models\File;
use App\Http\Requests\LeaveListRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class LeaveService
{
    const WORK_HOURS_PER_DAY = 8;  // 每天上班時數

    //  1. 申請請假
    // 根據前端送來的資料，算好請假時數，然後寫入資料庫
    // 生理假已加上每月重置判斷
    public function applyLeave(array $data): Leave
    {
        $user = auth()->user();

        // 1️⃣ 先計算這次請假有幾小時
        $leaveTypeId = $data['leave_type']; // 注意這裡
        $hours = $this->calculateHours($data['start_time'], $data['end_time']);

        // 2️⃣ 拿到這個假別的總時數
        $remainingHours = $this->getRemainingLeaveHours($leaveTypeId, $user->id);

        // 3️⃣ 判斷剩餘時數夠不夠
        if (!is_null($remainingHours) && $remainingHours < $hours) {
            throw new \Exception("剩餘時數不足，僅剩 {$remainingHours} 小時", 400);
        }

        // 4️⃣ 真的可以請，建立假單
        $leave = Leave::create([
            'user_id' => $user->id,
            'leave_type_id' => $data['leave_type'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'leave_hours' => $hours,
            'reason' => $data['reason'] ?? '',
            'status' => 'pending',
        ]);

        // 5️⃣ 處理附件
        if (!empty($data['attachment']) && $data['attachment']->isValid()) {
            $file = $data['attachment'];

            $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $attachmentPath = $file->storeAs('attachments', $filename, 'public');

            $fileRecord = File::create([
                'user_id' => $user->id,
                'leave_id' => $leave->id,
                'leave_attachment' => $attachmentPath,
            ]);

            // 把 files 的 id 存回 leaves 的 attachment_id
            $leave->update(['attachment' => $fileRecord->id]);
        }

        return $leave;
    }

    // 2. 查詢個人全部請假紀錄 (員工、主管、HR)
    public function getLeaveList($user, array $filters)
    {
        $query = Leave::with('user')->where('user_id', $user->id);
        $this->applyFilters($query, $filters);

        return $query->orderBy('start_time', 'desc')->paginate(8);
    }

    // 3. 查詢「部門」請假紀錄（主管、HR）
    public function getDepartmentLeaveList($user, array $filters)
    {
        $query = Leave::with('user')
            ->whereHas('user.employee', fn($q) => $q->where('department_id', $user->employee->department_id));

        // ✅ 確保過濾條件生效
        $this->applyFilters($query, $filters);

        return $query->select('leaves.*')->orderBy('start_time', 'desc')->paginate(10);
    }

    // 4. 查詢「全公司」請假紀錄（HR）
    public function getCompanyLeaveList(array $filters)
    {
        Log::info('getCompanyLeaveList called with filters:', $filters);

        $query = Leave::with('user'); // 確保載入關聯資料

        // ✅ 確保過濾條件生效
        $this->applyFilters($query, $filters);

        // 查詢所有請假單，分頁 10 筆
        $leaves = $query->select('leaves.*')->orderBy('start_time', 'desc')->paginate(10);

        Log::info('Query Result:', ['leaves' => $leaves->items()]);

        return $leaves;
    }

    // 5. 更新單筆紀錄
    public function updateLeave(Leave $leave, array $data): Leave
    {
        // 1️⃣ 計算請假小時數
        $hours = isset($data['start_time']) && isset($data['end_time'])
            ? $this->calculateHours($data['start_time'], $data['end_time'])
            : $leave->leave_hours;

        // 2️⃣ **檢查是否有舊的附件**
        $oldFile = File::where('id', $leave->attachment)->first();
        $oldFilePath = $oldFile ? $oldFile->leave_attachment : null;

        // 3️⃣ **處理新附件上傳**
        $attachmentId = $leave->attachment; // 預設保留舊的 `files.id`

        if (isset($data['attachment']) && $data['attachment']->isValid()) {
            $file = $data['attachment'];
            $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $filePath = $file->storeAs('attachments', $filename, 'public');

            // 4️⃣ **刪除舊附件，但不刪除 `files` 紀錄**
            if ($oldFilePath && Storage::disk('public')->exists($oldFilePath)) {
                Storage::disk('public')->delete($oldFilePath);
                Log::info("舊附件刪除成功: " . $oldFilePath);
            } else {
                Log::warning("舊附件不存在或無法刪除: " . $oldFilePath);
            }

            // 5️⃣ **更新 `files` 表內的 `leave_attachment`，不刪除 `files.id`**
            if ($oldFile) {
                $oldFile->update([
                    'leave_attachment' => $filePath, // 直接更新原來的紀錄
                ]);
                $attachmentId = $oldFile->id; // 保持原來的 `files.id`
            } else {
                // **如果沒有舊紀錄，就新增新的 `files` 紀錄**
                $fileRecord = File::create([
                    'user_id' => auth()->user()->id,
                    'leave_id' => $leave->id,
                    'leave_attachment' => $filePath,
                ]);
                $attachmentId = $fileRecord->id;
            }
        } else {
            // 如果沒有新附件，則保持原本的 `attachment` ID
            $attachmentId = $leave->attachment;
        }

        // 8️⃣ **更新 `leaves` 表**
        $leave->update([
            'leave_type_id' => $data['leave_type'] ?? $leave->leave_type_id,
            'start_time' => $data['start_time'] ?? $leave->start_time,
            'end_time' => $data['end_time'] ?? $leave->end_time,
            'leave_hours' => $hours,
            'reason' => $data['reason'] ?? $leave->reason,
            'status' => $data['status'] ?? $leave->status,
            'attachment' => $attachmentId,
        ]);

        Log::info("leaves.attachment 更新完成", [
            'leave_id' => $leave->id,
            'attachment_id' => $attachmentId
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
    public function getRemainingLeaveHours($leaveTypeId, $userId)
    {
        // 獲取該假別的總小時數
        $leaveType = LeaveType::find($leaveTypeId);

        if (is_null($leaveType->total_hours)) {
            return null;  // 用null當作「不需要檢查上限」
        }

        $totalHours = $leaveType->total_hours;

        // 計算該使用者已請的總小時數
        $usedHours = Leave::where('user_id', $userId)
            ->where('leave_type_id', $leaveTypeId)
            ->where('status', 'approved')  // 只算批准的
            ->sum('leave_hours');

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
            $query->where('leave_type_id', $filters['leave_type']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
    }
}


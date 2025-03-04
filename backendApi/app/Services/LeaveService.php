<?php

namespace App\Services;

use App\Models\Leave;
use Illuminate\Support\Str;

class LeaveService
{
    const WORK_HOURS_PER_DAY = 8;  // 每天上班時數

    /**
     * 申請請假
     * 根據前端送來的資料，算好請假時數，然後寫入資料庫
     */
    public function applyLeave(array $data): Leave
    {
        $hours = $this->calculateHours($data['start_time'], $data['end_time']);

        $attachmentPath = null;
        if (isset($data['attachment']) && $data['attachment']->isValid()) {
            $file = $data['attachment'];

            // 取得原始檔名和副檔名
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();

            // UUID檔名 + 副檔名
            $filename = Str::uuid() . '.' . $extension;

            // 存檔到attachments
            $attachmentPath = $file->storeAs('attachments', $filename, 'public');
        }

        return Leave::create([
            'user_id' => $data['user_id'],
            'leave_type' => $data['leave_type'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'leave_hours' => $hours,   // 存小時數
            'reason' => $data['reason'] ?? '',
            'status' => 'pending',
            'attachment' => $attachmentPath,
        ]);
    }

    /**
     * 計算跨天請假時數 (支援單日、跨日)
     */
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

    /**
     * 計算單天請假時數 (考慮上下班時間)
     */
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

    // 查詢個人請假紀錄清單
    public function getLeaveList(int $userId, array $filters)
    {
        $user = Leave::with('user')  // 順便帶user
            ->where('user_id', $userId);

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $user->where(function ($query) use ($filters) {
                $query->whereBetween('start_time', [$filters['start_date'] . ' 00:00:00', $filters['end_date'] . ' 23:59:59'])
                    ->orWhereBetween('end_time', [$filters['start_date'] . ' 00:00:00', $filters['end_date'] . ' 23:59:59'])
                    ->orWhere(function ($query) use ($filters) {
                        $query->where('start_time', '<=', $filters['start_date'] . ' 00:00:00')
                            ->where('end_time', '>=', $filters['end_date'] . ' 23:59:59');
                    });
            });
        }

        if (!empty($filters['leave_type'])) {
            $user->where('leave_type', $filters['leave_type']);
        }

        if (!empty($filters['status'])) {
            $user->where('status', $filters['status']);
        }

        return $user->orderBy('start_time', 'desc')->get();  // 回傳Collection
    }
}

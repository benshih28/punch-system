<?php

namespace App\Services;

use App\Models\Leave;

class LeaveService
{
    /**
     * 申請請假
     */
    public function applyLeave(array $data): Leave
    {
        return Leave::create([
            'user_id' => $data['user_id'],
            'leave_type' => $data['leave_type'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'leave_hours' => $data['hours'],
            'reason' => $data['reason'] ?? '',
            'status' => 'pending',
        ]);
    }

    /**
     * 更新請假
     */
    public function updateLeave(Leave $leave, array $data): void
    {
        $leave->update([
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'leave_hours' => $data['hours'],
            'reason' => $data['reason'] ?? '',
        ]);
    }
}

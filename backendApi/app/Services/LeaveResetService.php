<?php

namespace App\Services;

use App\Models\LeaveType;
use App\Models\LeaveResetRule;
use App\Models\User;
use App\Models\Leave;
use Illuminate\Support\Facades\Log;
use App\Models\EmployeeProfile;

class LeaveResetService
{
    public function checkAndResetLeave($leaveTypeId, $userId)
    {
        $rule = LeaveResetRule::where('leave_type_id', $leaveTypeId)->first();

        if (!$rule) {
            return; // 沒規則直接跳過
        }

        $now = now();

        if ($rule->rule_type === 'yearly') {
            [$month, $day] = explode('-', $rule->rule_value);
            $resetDate = $now->copy()->setMonth($month)->setDay($day)->startOfDay();
        } elseif ($rule->rule_type === 'monthly') {
            $resetDate = $now->copy()->startOfMonth()->setDay($rule->rule_value);
        } else {
            return; // 未知規則，直接跳過
        }

        $lastLeave = Leave::where('user_id', $userId)
            ->where('leave_type_id', $leaveTypeId)
            ->where('status', 'approved')
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$lastLeave || $lastLeave->created_at < $resetDate) {
            Leave::where('user_id', $userId)
                ->where('leave_type_id', $leaveTypeId)
                ->where('status', 'approved')
                ->update(['leave_hours' => 0]);

            Log::info("✅ User $userId 的 leaveType $leaveTypeId 已於 $resetDate 重置");
        }
    }

    /**
     * 計算剩餘假別小時數（支援特休年資遞增）
     */
    public function getRemainingLeaveHours($leaveTypeId, $userId)
    {
        $leaveType = LeaveType::find($leaveTypeId);

        if (is_null($leaveType->total_hours)) {
            return null;
        }

        if ($leaveType->name === 'annual leave') {
            $profile = EmployeeProfile::where('user_id', $userId)->first();

            if (!$profile || is_null($profile->hire_date)) {
                throw new \Exception('找不到員工的到職日，無法計算特休年資');
            }

            $years = $profile->hire_date->diffInYears(now());
            $months = $profile->hire_date->diffInMonths(now());

            // 依年資計算特休天數
            $extraDays = $this->calculateAnnualLeaveDays($years, $months);
            $extraHours = $extraDays * 8;

            $baseHours = 0; // 特休base是0，全部靠年資加上去
            $totalHours = $baseHours + $extraHours;
        } else {
            $totalHours = $leaveType->total_hours;
        }

        $usedHours = Leave::where('user_id', $userId)
            ->where('leave_type_id', $leaveTypeId)
            ->where('status', 'approved')
            ->sum('leave_hours');

        return $totalHours - $usedHours;
    }

    /**
     * 計算特休天數（依照勞基法規則）
     */
    private function calculateAnnualLeaveDays($years, $months): int
    {
        if ($months >= 6 && $years < 1) {
            return 3;
        } elseif ($years >= 1 && $years < 2) {
            return 7;
        } elseif ($years >= 2 && $years < 3) {
            return 10;
        } elseif ($years >= 3 && $years < 5) {
            return 14;
        } elseif ($years >= 5 && $years < 10) {
            return 15;
        } elseif ($years >= 10) {
            return min(15 + ($years - 5), 30);
        } else {
            return 0;  // 不滿6個月沒特休
        }
    }
}

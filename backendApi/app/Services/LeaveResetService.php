<?php

namespace App\Services;

use App\Models\LeaveType;
use App\Models\Leave;
use App\Models\LeaveResetRule;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use App\Models\EmployeeProfile;

class LeaveResetService
{
    // 特休假及生理假重置判斷
    // 1. 依照 'yearly' 或 'monthly' 判斷是每年重置還是每月重置
    public function checkAndResetLeave($leaveTypeId, $userId)
    {
        $rule = LeaveResetRule::where('leave_type_id', $leaveTypeId)->first();

        if (!$rule->rule_value) {
            return; // 避免 rule_value 是空的導致錯誤
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
            ->where('status', 3)
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$lastLeave || $lastLeave->created_at < $resetDate) {
            Leave::where('user_id', $userId)
                ->where('leave_type_id', $leaveTypeId)
                ->where('status', 3)
                ->update(['leave_hours' => 0]);

            // Log::info("✅ User $userId 的 leaveType $leaveTypeId 已於 $resetDate 重置");
        }
    }

    /**
     * 2. 計算剩餘假別小時數（支援特休年資遞增）
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

            $baseHours = 0; // 初始為 0，年資增加額外特休
            $totalHours = $baseHours + $extraHours;
        } elseif ($leaveType->name === 'Menstrual Leave') {
            // 每月 8 小時
            $totalHours = 8;

            // 取得本月已使用的生理假時數
            $usedHours = Leave::where('user_id', $userId)
                ->where('leave_type_id', $leaveTypeId)
                ->where('status', 0)
                ->whereMonth('start_time', now()->month) // 只計算當月的
                ->sum('leave_hours');
        } else {
            $totalHours = $leaveType->total_hours;

            // 計算所有已批准的時數
            $usedHours = Leave::where('user_id', $userId)
                ->where('leave_type_id', $leaveTypeId)
                ->where('status', 3)
                ->sum('leave_hours');
        }

        return max($totalHours - $usedHours, 0); // 確保不會變成負數
    }


    /**
     * 3. 計算特休天數（依照勞基法規則）
     */
    private function calculateAnnualLeaveDays($years, $months): int
    {
        switch (true) {
            case ($months >= 6 && $years < 1):
                return 3;
            case ($years >= 1 && $years < 2):
                return 7;
            case ($years >= 2 && $years < 3):
                return 10;
            case ($years >= 3 && $years < 5):
                return 14;
            case ($years >= 5 && $years < 10):
                return 15;
            case ($years >= 10):
                return min(15 + ($years - 10), 30);
            default:
                return 0;  // 不滿6個月沒特休
        }
    }
}

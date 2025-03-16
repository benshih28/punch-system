<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\LeaveType;
use App\Models\LeaveBalance;
use Carbon\Carbon;

class LeaveBalanceService
{
    /**
     * 計算特休假時數 (根據台灣公司規則)
     */
    public function calculateAnnualLeaveHours(Employee $employee)
    {
        $startDate = Carbon::parse($employee->start_date);
        $currentDate = Carbon::now();
        $monthsWorked = $startDate->diffInMonths($currentDate);
        $yearsWorked = $startDate->diffInYears($currentDate);
    
        // **未滿 6 個月無特休**
        if ($monthsWorked < 6) {
            return 0;
        }
    
        // **6 個月但未滿一年**
        if ($monthsWorked >= 6 && $monthsWorked < 12) {
            return 3 * 8; // 3 天 * 8 小時 = 24 小時
        }
    
        // **年資對應的特休天數**
        $annualLeaveDays = match (true) {
            $yearsWorked == 1 => 7, // 第 1 年：7 天
            $yearsWorked == 2 => 10, // 第 2 年：10 天
            $yearsWorked == 3 => 14, // 第 3 年：14 天
            $yearsWorked >= 5 && $yearsWorked < 10 => 15, // 第 5-9 年：15 天
            $yearsWorked >= 10 => min(30, 15 + ($yearsWorked - 10)), // 每年 +1，最高 30 天
            default => 0
        };
    
        return $annualLeaveDays * 8; // 轉換為小時
    }
    
    /**
     * 初始化請假餘額
     */
    public function initializeLeaveBalances(Employee $employee)
    {
        $gender = $employee->user->gender;
        $leaveTypes = LeaveType::all();

        foreach ($leaveTypes as $leaveType) {
            // 🔹 性別限制
            if ($leaveType->gender_limit && $leaveType->gender_limit !== $gender) {
                continue;
            }

            // 🔹 設定特休假
            $remainingHours = $leaveType->code === 'annual'
                ? $this->calculateAnnualLeaveHours($employee)
                : ($leaveType->default_hours ?? 0);

            LeaveBalance::updateOrCreate(
                ['employee_id' => $employee->id, 'leave_type_id' => $leaveType->id],
                ['remaining_hours' => $remainingHours]
            );
        }
    }
}

<?php

namespace App\Services;

use App\Models\LeaveType;
use App\Models\Leave;
use Illuminate\Support\Facades\Log;
use App\Models\EmployeeProfile;
use Carbon\Carbon;

class LeaveResetService
{
    //  1. 剩餘特休時數
    public function getRemainingAnnualLeaveHours($userId, $leaveStartTime, $excludeLeaveId = null)
    {
        // ✅ **解析使用者請假的時間**
        $leaveDate = Carbon::parse($leaveStartTime, 'Asia/Taipei');
        $year = $leaveDate->year; // 取得請假年份

        // ✅ **獲取該年份的特休時數**
        $totalHours = $this->getAnnualLeaveHours($userId, $leaveDate);

        // Log::info("🟡 員工 {$userId} 可用的特休時數 ({$year} 年): {$totalHours}");

        // ✅ **查詢該年度已請的特休時數**
        $usedHoursQuery = Leave::where('user_id', $userId)
            ->whereHas('leaveType', function ($query) {
                $query->where('name', 'Annual Leave');
            })
            ->whereIn('status', [0, 1, 3]) // ✅ 只計算「待審核、已批准」的假單
            ->whereYear('start_time', $year); // ✅ 確保是當年度的特休

        // 若為編輯假單，排除當前假單
        if (!is_null($excludeLeaveId)) {
            $usedHoursQuery->where('id', '!=', $excludeLeaveId);
        }

        $usedHoursSum = $usedHoursQuery->sum('leave_hours');

        // Log::info("🟣 員工 {$userId} 已請的特休時數 ({$year} 年): {$usedHoursSum}");

        // ✅ **確保特休時數不為負數**
        $remainingHours = max($totalHours - $usedHoursSum, 0);

        // Log::info("🟡 員工 {$userId} 剩餘可請特休時數 ({$year} 年): {$remainingHours}");

        return $remainingHours;
    }


    // 2. 計算特休天數（依照勞基法規則）
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

    // 3. 計算員工特休時數(自動新增特休)
    public function getAnnualLeaveHours($userId, $leaveStartTime)
    {
        $profile = EmployeeProfile::where('employee_id', $userId)->first();

        if (!$profile || !$profile->hire_date) {
            // Log::warning("⚠️ 員工 {$userId} 沒有找到 hire_date，無法計算特休");
            return 0;
        }

        // ✅ **動態計算年資**
        $leaveDate = Carbon::parse($leaveStartTime);
        $years = $profile->getYearsOfServiceAttribute($leaveDate); // ✅ 使用 `getYearsOfServiceAttribute()`
        $months = $profile->getMonthsOfServiceAttribute($leaveDate); // ✅ 也計算月數

        // Log::info("🟢 員工 {$userId} 的年資: {$years} 年, {$months} 月");

        // ✅ **計算該年度可請的特休天數**
        $newAnnualLeaveDays = $this->calculateAnnualLeaveDays($years, $months);
        $newAnnualLeaveHours = $newAnnualLeaveDays * 8;

        // Log::info("🟢 計算出的特休: {$newAnnualLeaveDays} 天 ({$newAnnualLeaveHours} 小時)");

        return $newAnnualLeaveHours;
    }

    // 4. 重置員工生理假剩餘時數
    public function resetMenstrualLeaveHours($userId, $leaveStartTime)
    {
        // ✅ **根據使用者輸入的請假時間，決定該請假屬於哪一個月份**
        $leaveDate = Carbon::parse($leaveStartTime, 'Asia/Taipei');

        // ✅ **找「上個月」的時間範圍**
        $lastMonthStart = $leaveDate->copy()->subMonth()->startOfMonth();
        $lastMonthEnd = $leaveDate->copy()->subMonth()->endOfMonth();

        // Log::info("🟠 查詢上個月假單時間範圍: " . $lastMonthStart . " ~ " . $lastMonthEnd);

        // ✅ **每個月固定 8 小時**
        $maxHours = LeaveType::where('name', 'Menstrual Leave')->value('total_hours') ?? 8;

        // ✅ **計算上個月已使用的生理假時數**
        $lastMonthUsedHours = Leave::where('user_id', $userId)
            ->whereHas('leaveType', function ($query) {
                $query->where('name', 'Menstrual Leave');
            })
            ->whereIn('status', [0, 1, 3]) // ✅ 包含「待審核」、「主管通過」、「HR 通過」
            ->whereBetween('start_time', [$lastMonthStart, $lastMonthEnd])
            ->sum('leave_hours');

        // Log::info("🔴 上個月請假時數: " . $lastMonthUsedHours);

        // ✅ **當月的總額度 = 8 小時 + 上個月使用的時數（最多 8 小時）**
        $resetHours = min($maxHours, $lastMonthUsedHours);
        // Log::info("🟢 修正後的補回時數: " . $resetHours);

        return $resetHours;
    }

    // 5. 計算生理假剩餘時數
    public function getRemainingMenstrualLeaveHours($userId, $leaveStartTime, $excludeLeaveId = null)
    {
        $leaveDate = Carbon::parse($leaveStartTime, 'Asia/Taipei'); // ✅ 依據使用者輸入的時間來決定月份
        $maxHours = LeaveType::where('name', 'Menstrual Leave')->value('total_hours') ?? 8;

        // ✅ **當月範圍**
        $thisMonthStart = $leaveDate->copy()->startOfMonth();
        $thisMonthEnd = $leaveDate->copy()->endOfMonth();

        // Log::info("🟠 查詢當月假單時間範圍: " . $thisMonthStart . " ~ " . $thisMonthEnd);

        // ✅ **計算當月已批准的請假時數**
        $approvedHours = Leave::where('user_id', $userId)
            ->whereHas('leaveType', function ($query) {
                $query->where('name', 'Menstrual Leave');
            })
            ->whereIn('status', [1, 3])
            ->whereBetween('start_time', [$thisMonthStart, $thisMonthEnd])
            ->sum('leave_hours');

        // Log::info("🟣 當月已批准請假時數: " . $approvedHours);

        // ✅ **計算當月待審核的請假時數**
        $pendingQuery = Leave::where('user_id', $userId)
            ->whereHas('leaveType', function ($query) {
                $query->where('name', 'Menstrual Leave');
            })
            ->where('status', 0)
            ->whereBetween('start_time', [$thisMonthStart, $thisMonthEnd]);
        // Log::info("🔍 查詢 SQL: " . $pendingHours->toSql(), $pendingHours->getBindings());
        if (!is_null($excludeLeaveId)) {
            $pendingQuery->where('id', '!=', $excludeLeaveId);
        }
        
        $pendingHours = $pendingQuery->sum('leave_hours');

        // ✅ **補回的生理假時數**
        $resetHours = $this->resetMenstrualLeaveHours($userId, $leaveStartTime);

        // ✅ **當月總額度 = 8 小時 + 上個月請假時數（最多 8 小時）**
        $totalAvailableHours = min($maxHours, $resetHours + $maxHours);

        // ✅ **計算總已請假時數**
        $usedHours = $approvedHours + $pendingHours;

        // ✅ **計算剩餘可請時數**
        $remainingHours = max($totalAvailableHours - $usedHours, 0);

        // Log::info("🔵 當月最大可請時數: " . $maxHours);
        // Log::info("🟢 修正後的補回額度 (resetHours): " . $resetHours);
        // Log::info("🟣 當月已請總時數 (已批准 + 待審核): " . $usedHours);
        // Log::info("🟡 剩餘可請時數: " . $remainingHours);

        return $remainingHours;
    }

    // 6. 計算剩餘的假別時數（不包含特休 & 生理假）
    public function getRemainingLeaveHours($leaveTypeId, $userId, $leaveStartTime = null, $excludeLeaveId = null)
    {
        $leaveType = LeaveType::find($leaveTypeId);

        if (!$leaveType) {
            Log::warning("⚠️ 假別 ID {$leaveTypeId} 不存在，回傳 0");
            return 0;
        }

        Log::info("🟢 假別名稱: " . json_encode($leaveType->name));

        // ✅ **檢查 `leaveType->name` 是否真的等於 `Annual leave`**
        if (trim(strtolower($leaveType->name)) === 'annual leave') {
            Log::info("✅ 這是特休 (`Annual Leave`)，進入 `getRemainingAnnualLeaveHours()`！");
            return $this->getRemainingAnnualLeaveHours($userId, $leaveStartTime, $excludeLeaveId);
        }

        // ✅ **檢查 `leaveType->name` 是否等於 `Menstrual Leave`**
        if (trim(strtolower($leaveType->name)) === 'menstrual leave') {
            Log::info("✅ 這是生理假 (`Menstrual Leave`)，進入 `getRemainingMenstrualLeaveHours()`！");
            return $this->getRemainingMenstrualLeaveHours($userId, $leaveStartTime, $excludeLeaveId);
        }

        // ✅ **只有「一般假別」才需要處理 `total_hours`**
        if (is_null($leaveType->total_hours)) {
            Log::warning("⚠️ 假別 `{$leaveType->name}` 沒有時數上限，回傳 5000");
            return 5000;
        }

        // **計算已使用時數**
        $totalHours = $leaveType->total_hours;
        $usedHoursQuery = Leave::where('user_id', $userId)
            ->where('leave_type_id', $leaveTypeId)
            ->whereIn('status', [0, 1, 3]);

        if (!is_null($excludeLeaveId)) {
            $usedHoursQuery->where('id', '!=', $excludeLeaveId);
        }

        $usedHours = $usedHoursQuery->sum('leave_hours');

        return max($totalHours - $usedHours, 0);
    }
}

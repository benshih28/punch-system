<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LeaveType;

class LeaveTypesSeeder extends Seeder
{
    public function run()
    {
        $leaveTypes = [
            ['name' => '特休假', 'code' => 'annual', 'default_hours' => 120, 'gender_limit' => null], // 預設 120 小時
            ['name' => '病假', 'code' => 'sick', 'default_hours' => 56, 'gender_limit' => null], // 預設 56 小時
            ['name' => '生理假', 'code' => 'menstrual', 'default_hours' => 12, 'gender_limit' => 'female'], // 限女性
            ['name' => '產假', 'code' => 'maternity', 'default_hours' => 56, 'gender_limit' => 'female'], // 限女性
            ['name' => '陪產假', 'code' => 'paternity', 'default_hours' => 7, 'gender_limit' => 'male'], // 限男性
            ['name' => '事假', 'code' => 'personal', 'default_hours' => null, 'gender_limit' => null], // 無固定時數
            ['name' => '公假', 'code' => 'official', 'default_hours' => null, 'gender_limit' => null], // 無固定時數
        ];

        foreach ($leaveTypes as $type) {
            LeaveType::firstOrCreate(['code' => $type['code']], $type);
        }
    }
}

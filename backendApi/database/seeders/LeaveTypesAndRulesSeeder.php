<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LeaveType;
use App\Models\LeaveResetRule;


class LeaveTypesAndRulesSeeder extends Seeder
{
    public function run()
    {
        // 1️⃣ 填充 leave_types 資料表
        $leaveTypes = [
            ["name" => "Personal Leave", "description" => "事假", "total_hours" => null],
            ["name" => "Sick Leave", "description" => "病假", "total_hours" => null],
            ["name" => "Official Leave", "description" => "公假", "total_hours" => null],
            ["name" => "Menstrual Leave", "description" => "生理假", "total_hours" => 8],
            ["name" => "Annual Leave", "description" => "特休假", "total_hours" => null],
            ["name" => "Marriage Leave", "description" => "婚假", "total_hours" => 24],
            ["name" => "Maternity Leave", "description" => "產假", "total_hours" => 320],
            ["name" => "Immediate Family Bereavement Leave", "description" => "喪假(父母、配偶)", "total_hours" => 64],
            ["name" => "Close Family Bereavement Leave", "description" => "喪假(祖父母、子女)", "total_hours" => 40],
            ["name" => "Extended Family Bereavement Leave", "description" => "喪假(兄弟姊妹、岳父母)", "total_hours" => 24],
        ];

        foreach ($leaveTypes as $type) {
            LeaveType::firstOrCreate(['name' => $type['name']], $type);
        }

        // 2️⃣ 填充 leave_reset_rules 資料表
        $leaveResetRules = [
            ["leave_type_id" => 4, "rule_type" => "monthly", "rule_value" => "01"],
            ["leave_type_id" => 5, "rule_type" => "yearly", "rule_value" => "01-01"],
        ];

        foreach ($leaveResetRules as $rule) {
            LeaveResetRule::firstOrCreate(['leave_type_id' => $rule['leave_type_id']], $rule);
        }
    }
}

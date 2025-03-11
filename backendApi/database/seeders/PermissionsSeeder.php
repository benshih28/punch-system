<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionsSeeder extends Seeder
{
    public function run()
    {
        $permissions = [
            // 🔹 1️⃣ 基本考勤權限
            'punch_in', // 員工可以「上班打卡」
            'punch_out', // 員工可以「下班打卡」
            'request_correction', // 員工可以「申請補打卡」
            'view_corrections', // 員工可以「查詢個人補登打卡紀錄」
            'view_attendance', // 員工可以「查詢個人打卡紀錄」
            'approve_correction', // 主管/HR 可以「審核補打卡」
            'view_all_corrections', // HR 可以「人資查詢所有補登打卡紀錄」

            // 🔹 2️⃣ 請假管理
            'request_leave', // 員工可以「申請請假」
            'approve_leave', // 主管/HR 可以「審核請假」
            'view_leave_records', // 員工/主管/HR 可以「查詢請假紀錄」
            'delete_leave', // 員工可以「刪除請假資料」
            'view_department_leave_records', // 主管/HR 可以「查看部門請假紀錄」
            'view_company_leave_records', // HR 可以「查看全公司請假紀錄」
            'approve_department_leave', // 主管/HR 可以「核准/駁回本部門請假單」

            // 🔹 3️⃣ 角色與權限管理（HR 專用）
            'manage_roles', // HR 可以「建立角色、取得所有角色、指派權限、移除權限、取得角色權限」
            'view_roles', // 取得「使用者的角色」
            'view_permissions', // 取得「使用者的權限」

            // 🔹 4️⃣ 員工與組織管理
            'manage_employees', // HR 可以「取得所有員工」
            'register_employee', // HR 可以「註冊員工」
            'review_employee', // HR 可以「審核註冊資料」
            'assign_employee_details', // HR 可以「分配/變更部門、職位、主管、角色」
            'delete_employee', // HR 可以「刪除員工」

            // 🔹 5️⃣ 部門與職位管理（HR 專用）
            'manage_departments', // HR 可以「新增、修改、刪除部門」
            'manage_positions', // HR 可以「新增、修改、刪除職位」

            // 🔹 其他
            'view_manager', // 員工可以「查詢主管」
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }
    }
}

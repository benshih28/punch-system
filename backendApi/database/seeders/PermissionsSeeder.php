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
            ['name' => 'punch_in', 'category' => '基本考勤權限'],
            ['name' => 'punch_out', 'category' => '基本考勤權限'],
            ['name' => 'request_correction', 'category' => '基本考勤權限'],
            ['name' => 'view_corrections', 'category' => '基本考勤權限'],
            ['name' => 'view_attendance', 'category' => '基本考勤權限'],
            ['name' => 'approve_correction', 'category' => '基本考勤權限'],
            ['name' => 'view_all_corrections', 'category' => '基本考勤權限'],
    
            // 🔹 2️⃣ 請假管理
            ['name' => 'request_leave', 'category' => '請假管理'],
            ['name' => 'view_leave_records', 'category' => '請假管理'],
            ['name' => 'approve_leave', 'category' => '請假管理'],
            ['name' => 'delete_leave', 'category' => '請假管理'],
            ['name' => 'view_department_leave_records', 'category' => '請假管理'],
            ['name' => 'view_company_leave_records', 'category' => '請假管理'],
            ['name' => 'approve_department_leave', 'category' => '請假管理'],
            ['name' => 'update_leave', 'category' => '請假管理'],
    
            // 🔹 3️⃣ 角色與權限管理
            ['name' => 'manage_roles', 'category' => '角色與權限管理'],
            ['name' => 'view_roles', 'category' => '角色與權限管理'],
            ['name' => 'view_permissions', 'category' => '角色與權限管理'],
    
            // 🔹 4️⃣ 員工與組織管理
            ['name' => 'manage_employees', 'category' => '員工與組織管理'],
            ['name' => 'register_employee', 'category' => '員工與組織管理'],
            ['name' => 'review_employee', 'category' => '員工與組織管理'],
            ['name' => 'assign_employee_details', 'category' => '員工與組織管理'],
            ['name' => 'delete_employee', 'category' => '員工與組織管理'],
    
            // 🔹 5️⃣ 部門與職位管理 (HR)
            ['name' => 'manage_departments', 'category' => '部門與職位管理 (HR)'],
            ['name' => 'manage_positions', 'category' => '部門與職位管理 (HR)'],
            ['name' => 'view_manager', 'category' => '部門與職位管理 (HR)'],
            ['name' => 'view_subordinates', 'category' => '部門與職位管理 (HR)'],
        ];
    
        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission['name'],
                'category' => $permission['category']
            ]);
        }
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionsSeeder extends Seeder
{
    public function run()
    {
        $permissions = [
            // 基本考勤權限
            'punch_in', 'punch_out', 'request_correction', 'view_corrections', 
            'view_attendance', 'approve_correction', 'view_all_corrections',

            // 請假管理
            'request_leave', 'approve_leave', 'view_leave_records', 'delete_leave',
            'view_department_leave_records', 'view_company_leave_records', 'approve_department_leave',

            // 角色與權限管理（HR 專用）
            'manage_roles', 'assign_roles', 'revoke_roles', 'view_roles', 'view_permissions',

            // 員工與組織管理
            'manage_employees', 'register_employee', 'review_employee', 'assign_department',
            'delete_employee', 'assign_manager',

            // 部門與職位管理（HR 專用）
            'manage_departments', 'manage_positions',

            // 主管與員工關係
            'view_manager', 'view_subordinates'
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }
    }
}

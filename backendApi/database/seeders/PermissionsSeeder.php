<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionsSeeder extends Seeder
{
    public function run()
    {
        $permissions = [
            // 🔹 基本考勤權限
            'punch_in', 'punch_out', 'request_correction', 'approve_correction', 'view_attendance',
            // 🔹 請假管理
            'request_leave', 'approve_leave', 'view_leave_records',
            // 🔹 角色與權限管理
            'manage_roles', 'assign_roles', 'revoke_roles', 'manage_permissions',
            // 🔹 員工與組織管理
            'view_employees', 'edit_employees', 'delete_employees', 'assign_department', 'assign_position', 'assign_manager',
            // 🔹 部門與職位管理
            'manage_departments', 'manage_positions',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }
    }
}
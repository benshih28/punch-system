<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use App\Models\Employee;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class RolesSeeder extends Seeder
{
    public function run()
    {
        $roles = [
            'admin' => Permission::all(),
            'HrManager' => Permission::whereIn('name', [
                'manage_employees', 'manage_roles', 'view_company_leave_records',
                'approve_leave', 'view_department_leave_records', 'manage_departments',
                'manage_positions', 'approve_department_leave', 'correct_leave',
                'update_leave', 'delete_leave'
            ])->get(),
            'HR' => Permission::whereIn('name', [
                'approve_leave', 'view_department_leave_records', 'manage_employees',
                'correct_leave', 'update_leave', 'delete_leave'
            ])->get(),
            'manager' => Permission::whereIn('name', [
                'approve_department_leave', 'view_department_leave_records'
            ])->get(),
            'employee' => Permission::whereIn('name', [
                'request_leave', 'view_leave_records', 'punch_in', 'punch_out',
                'update_leave', 'delete_leave'
            ])->get(),
        ];

        foreach ($roles as $roleName => $permissions) {
            $role = Role::firstOrCreate(['name' => $roleName]);
            $role->syncPermissions($permissions);
        }

        // ✅ **建立 `admin`**
        if (!User::where('email', 'admin@example.com')->exists()) {
            $admin = User::create([
                'name' => 'Admin',
                'email' => 'admin@example.com',
                'password' => Hash::make('Admin@123'),
                'gender' => 'male',
            ]);
            $admin->assignRole('admin');

            Employee::create([
                'user_id' => $admin->id,
                'department_id' => DB::table('departments')->where('name', '人資部')->value('id'),
                'start_date' => '2020-01-01',
                'status' => 'approved',
            ]);
        }

        // ✅ **建立 `HR Manager`**
        if (!User::where('email', 'hr@example.com')->exists()) {
            $hrUser = User::create([
                'name' => 'HR Manager',
                'email' => 'hr@example.com',
                'password' => Hash::make('Hr@123'),
                'gender' => 'female',
            ]);
            $hrUser->assignRole('HrManager');

            Employee::create([
                'user_id' => $hrUser->id,
                'department_id' => DB::table('departments')->where('name', '人資部')->value('id'),
                'start_date' => '2021-06-15',
                'status' => 'approved',
            ]);
        }

        // ✅ **建立 `Manager`**
        if (!User::where('email', 'manager@example.com')->exists()) {
            $managerUser = User::create([
                'name' => 'Manager',
                'email' => 'manager@example.com',
                'password' => Hash::make('Manager@123'),
                'gender' => 'male',
            ]);
            $managerUser->assignRole('manager');

            Employee::create([
                'user_id' => $managerUser->id,
                'department_id' => DB::table('departments')->where('name', '行政部')->value('id'),
                'start_date' => '2023-02-10',
                'status' => 'approved',
            ]);
        }

        // ✅ **建立 `員工`**
        if (!User::where('email', 'employee@example.com')->exists()) {
            $employeeUser = User::create([
                'name' => 'Employee',
                'email' => 'employee@example.com',
                'password' => Hash::make('Employee@123'),
                'gender' => 'male',
            ]);
            $employeeUser->assignRole('employee');

            Employee::create([
                'user_id' => $employeeUser->id,
                'department_id' => DB::table('departments')->where('name', '行政部')->value('id'),
                'start_date' => '2024-05-01',
                'status' => 'approved',
            ]);
        }
    }
}

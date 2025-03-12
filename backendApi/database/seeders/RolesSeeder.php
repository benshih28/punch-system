<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use App\Models\Employee;
use Illuminate\Support\Facades\Hash;

class RolesSeeder extends Seeder
{
    public function run()
    {
        // 1️⃣ **建立 `admin` 角色**
        $adminRole = Role::firstOrCreate(['name' => 'admin']);

        // 2️⃣ **將所有權限指派給 `admin`**
        $adminRole->syncPermissions(Permission::all());

        // 3️⃣ **檢查是否已有 `users`，如果沒有則建立一個 `admin`**
        if (!User::exists()) {
            $admin = User::create([
                'name' => 'Admin',
                'email' => 'admin@example.com',
                'password' => Hash::make('Admin@123'), // ✅ 預設密碼，可修改
                'gender' => 'male',
            ]);

            // 4️⃣ **指派 `admin` 角色**
            $admin->assignRole('admin');

            // 5️⃣ **建立 `employee` 記錄（若 `employees` 表需要對應）**
            Employee::create([
                'user_id' => $admin->id,
                'status' => 'approved', // ✅ `admin` 預設為通過審核
            ]);
        }
    }
}

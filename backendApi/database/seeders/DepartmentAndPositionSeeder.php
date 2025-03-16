<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class DepartmentAndPositionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 先插入部門
        $hrDepartmentId = DB::table('departments')->insertGetId([
            'name' => '人資部',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $adminDepartmentId = DB::table('departments')->insertGetId([
            'name' => '行政部',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 再插入職位，並綁定 `人資部`
        DB::table('positions')->insert([
            [
                'name' => '人資主管',
                'department_id' => $hrDepartmentId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => '人資',
                'department_id' => $hrDepartmentId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => '行政',
                'department_id' => $adminDepartmentId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => '行政主管',
                'department_id' => $adminDepartmentId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}

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

        // 再插入職位，並綁定 `人資部`
        DB::table('positions')->insert([
            'name' => '人資主管',
            'department_id' => $hrDepartmentId, // 綁定人資部
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}

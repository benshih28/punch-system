<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Department;

class DepartmentController extends Controller
{
    public function store(Request $request)
    {
        // 驗證請求資料
        $request->validate([
            'name' => 'required|string|unique:departments,name|max:255',
            'manager_id' => 'nullable|exists:users,id', // nullable允許為空值，確保manager_id存在於users表
        ]);

        // 建立部門
        $department = Department::create([
            'name' => $request->name,
        ]);

        // 回傳JSON
        return response()->json([
            'message' => '部門已新增',
            'department' => $department,
        ], 201);
    }

    // 取得所有部門
    public function index()
    {
        $departments = Department::with('manager')->get(); // 取得所有部門，並帶出主管資訊

        return response()->json([
            'message' => '成功獲取所有部門',
            'departments' => $departments
        ], 200);
    }


    // 取得特定部門
    public function show(string $name)
    {
        $department = Department::where('name', $name)->with('manager')->first();

        if (!$department) {
            return response()->json([
                'message' => '找不到該部門',
            ], 404);
        }

        return response()->json([
            'message' => '成功獲取部門',
            'department' => $department
        ], 200);
    }
}

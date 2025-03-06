<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Department;

class DepartmentController extends Controller
{
    // 取得所有部門
    public function index()
    {
        $departments = Department::with('name')->get(); // 取得所有部門，並帶出主管資訊
        $departments = Department::all(); // 取得所有部門

        return response()->json([
            'message' => '成功獲取所有部門',
            'departments' => $departments
        ], 200);
    }

    // 新增部門
    public function store(Request $request)
    {
        // 驗證請求資料
        $request->validate([
            'name' => 'required|string|unique:departments,name|max:255',
            // 'manager_id' => 'nullable|exists:users,id', // nullable允許為空值，確保manager_id存在於users表
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

    // 更新部門
    public function update(Request $request, $id)
    {
        $request->validate(['name' => 'required|string|unique:departments,name,' . $id]);

        $department = Department::findOrFail($id);
        $department->name = $request->name;
        $department->save();

        return response()->json(['message' => '部門更新成功'], 200); // 200 OK
    }

    // 刪除部門
    public function destroy($id)
    {
        $department = Department::find($id);
        
        if (!$department) {
            return response()->json(['error' => '找不到部門'], 404); // 404 Not Found
        }

        $department->delete();
        return response()->json(['message' => '部門刪除成功'], 200); // 200 OK
    }

}
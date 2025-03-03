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

        return response()->json(Department::all(), 200);
    }
    
    



    // 新增部門
    public function store(Request $request)
    {
        // 驗證請求資料
        $request->validate(['name' => 'required|string|unique:departments,name']);

        // 建立部門
        $department = Department::create([
            'name' => $request->name,
        ]);

        // 回傳JSON
        return response()->json([
            'message' => '部門已新增',
        ], 201); // 201 Created
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

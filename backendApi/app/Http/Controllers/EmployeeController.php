<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    // ✅ 取得所有員工列表（HR 介面）
    public function index()
    {
        return response()->json(Employee::all(), 200);
    }

    // ✅ HR 註冊新員工
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $employee = Employee::create([
            'user_id' => $request->user_id,
            'status' => 'pending'
        ]);

        return response()->json(['message' => '員工已註冊，等待審核'], 201);
    }

    // ✅ HR 批准 / 拒絕 員工註冊
    public function reviewEmployee(Request $request, $id)
    {
        $request->validate(['status' => 'required|in:approved,rejected']);
    
        $employee = Employee::find($id);
        if (!$employee) {
            return response()->json(['error' => '找不到員工'], 404);
        }
    
        if ($request->status === 'approved') {
            $employee->status = 'approved';
            $employee->save();
            return response()->json(['message' => '員工已批准'], 200);
        } elseif ($request->status === 'rejected') {
            // 🔹 先刪除 `users` 資料
            $user = $employee->user;
            if ($user) {
                $user->delete(); // 刪除使用者
            }
    
            // 🔹 刪除 `employees` 資料
            $employee->delete();
    
            return response()->json(['message' => '員工申請已拒絕，並刪除帳號'], 200);
        }
    }

    // ✅ HR 分配部門、職位、主管
    public function assignDepartmentAndPosition(Request $request, $id)
    {
        $request->validate([
            'department_id' => 'nullable|exists:departments,id',
            'position_id' => 'nullable|exists:positions,id',
            'manager_id' => 'nullable|exists:users,id'
        ]);

        $employee = Employee::find($id);
        if (!$employee) {
            return response()->json(['error' => '找不到員工'], 404);
        }

        $employee->department_id = $request->department_id;
        $employee->position_id = $request->position_id;
        $employee->manager_id = $request->manager_id;
        $employee->save();

        return response()->json(['message' => '員工資訊更新成功']);
    }

    // ✅ HR 刪除員工
    public function destroy($id)
    {
        $employee = Employee::find($id);
        if (!$employee) {
            return response()->json(['error' => '找不到員工'], 404);
        }

        $employee->delete();
        return response()->json(['message' => '員工刪除成功']);
    }

    // ✅ 員工查詢自己的主管
    public function getEmployeeManager($id)
    {
        $employee = Employee::with('manager')->find($id);

        if (!$employee) {
            return response()->json(['message' => '找不到員工'], 404);
        }

        return response()->json($employee->manager);
    }

    // ✅ 主管查詢自己管理的員工
    public function getMyEmployees()
    {
        $user = auth()->user();
        $employees = Employee::where('manager_id', $user->id)->get();

        if ($employees->isEmpty()) {
            return response()->json(['error' => '你沒有管理任何員工'], 403);
        }

        return response()->json($employees);
    }
}

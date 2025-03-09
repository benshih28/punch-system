<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    // 取得所有員工列表（HR 介面）
    public function index(Request $request)
    {
        // 取得查詢參數
        $search = $request->query('search'); // 員工姓名/Email
        $departmentId = $request->query('department_id'); // 部門篩選
        $positionId = $request->query('position_id'); // 職位篩選
        $status = $request->query('status'); // 員工狀態 (待審核 / 已批准 / 已拒絕)
        $perPage = $request->query('per_page', 10); // 預設 10 筆
        $page = $request->query('page', 1); // 預設第 1 頁
    
        // 取得員工清單 (使用 Laravel Query Builder)
        $query = Employee::query();
    
        // 🔍 搜尋員工姓名 或 Email
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                  ->orWhere('email', 'like', "%$search%");
            });
        }
    
        // 篩選部門
        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }
    
        // 篩選職位
        if ($positionId) {
            $query->where('position_id', $positionId);
        }
    
        // 篩選員工狀態
        if ($status) {
            $query->where('status', $status);
        }
    
        // 取得分頁數據
        $employees = $query
            ->with(['department', 'position']) // 關聯部門 & 職位
            ->paginate($perPage, ['*'], 'page', $page);
    
        // 返回標準 API 格式
        return response()->json([
            'message' => '成功獲取員工列表',
            'meta' => [
                'current_page' => $employees->currentPage(),
                'per_page' => $employees->perPage(),
                'total' => $employees->total(),
                'last_page' => $employees->lastPage(),
            ],
            'data' => $employees->items()
        ], 200);
    }

    // HR 註冊新員工
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

    // HR 批准 / 拒絕 員工註冊
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
            // 🔹 **不刪除員工，只是標記為 rejected**
            $employee->status = 'rejected';
            $employee->save();

            return response()->json(['message' => '員工申請已拒絕'], 200);
        }
    }

    // HR 分配部門、職位、主管
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

    // HR 刪除員工
    public function destroy($id)
    {
        $employee = Employee::find($id);
        if (!$employee) {
            return response()->json(['error' => '找不到員工'], 404);
        }

        // 🔹 先刪除 `users` 資料
        $user = $employee->user;
        if ($user) {
            $user->delete(); // 刪除 `users` 表的使用者
        }

        // 🔹 刪除 `employees` 資料
        $employee->delete();

        return response()->json(['message' => '員工已刪除'], 200);
    }

    // 員工查詢自己的主管
    public function getEmployeeManager($id)
    {
        $employee = Employee::with('manager')->find($id);

        if (!$employee) {
            return response()->json(['message' => '找不到員工'], 404);
        }

        return response()->json($employee->manager);
    }

    // 主管查詢自己管理的員工
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

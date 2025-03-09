<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\EmployeeService;
use Illuminate\Support\Facades\Auth;

class EmployeeController extends Controller
{
    protected $employeeService;

    public function __construct(EmployeeService $employeeService)
    {
        $this->employeeService = $employeeService;
    }

    /**
     * ✅ 取得所有員工列表（HR 介面）
     */
    public function index(Request $request): JsonResponse
    {
        $departmentId = $request->query('department_id');
        $roleId = $request->query('role_id');
        $userId = $request->query('user_id') ?: null;
        $perPage = $request->query('per_page', 10);
        $page = $request->query('page', 1);
        $offset = ($page - 1) * $perPage;

        $employees = $this->employeeService->getEmployees($departmentId, $roleId, $userId, $perPage, $offset);

        return response()->json([
            'message' => '成功獲取員工列表',
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => count($employees), // 這裡需要額外查詢總數
                'last_page' => ceil(count($employees) / $perPage),
            ],
            'data' => $employees
        ], 200);
    }

    /**
     * ✅ HR 註冊新員工
     */
    public function store(Request $request): JsonResponse
    {
        $request->merge(['email' => strtolower($request->email)]);

        // 🔹 驗證輸入資料
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'gender' => ['required', 'in:male,female'],
        ]);

        // 🔹 呼叫 `Service` 來執行 MySQL 預存程序
        $this->employeeService->createEmployee(
            $request->name,
            $request->email,
            $request->password,
            $request->gender
        );

        return response()->json(['message' => '員工已註冊，等待審核'], 201);
    }

    /**
     * ✅ HR 批准 / 拒絕 員工註冊
     */
    public function reviewEmployee(Request $request, $id): JsonResponse
    {
        $request->validate(['status' => 'required|in:approved,rejected']);

        $this->employeeService->reviewEmployee($id, $request->status);

        return response()->json(['message' => '員工審核狀態更新成功'], 200);
    }

    /**
     * ✅ HR 分配部門、職位、主管、角色
     */
    public function assignDepartmentAndPosition(Request $request, $id): JsonResponse
    {
        $request->validate([
            'department_id' => 'required|exists:departments,id',
            'position_id' => 'required|exists:positions,id',
            'manager_id' => 'required|exists:users,id',
            'role_id' => 'required|exists:roles,id' 
        ]);

        $this->employeeService->assignDepartmentAndPosition(
            $id, 
            $request->department_id,
            $request->position_id,
            $request->manager_id,
            $request->role_id
        );

        return response()->json(['message' => '員工資訊更新成功'], 200);
    }

    /**
     * ✅ HR 刪除員工
     */
    public function destroy($id): JsonResponse
    {
        $this->employeeService->deleteEmployee($id);
        return response()->json(['message' => '員工已刪除'], 200);
    }

    /**
     * ✅ 員工查詢自己的主管
     */
    public function getEmployeeManager($id): JsonResponse
    {
        $manager = $this->employeeService->getEmployeeManager($id);

        if (!$manager) {
            return response()->json(['message' => '找不到員工'], 404);
        }

        return response()->json($manager);
    }

    /**
     * ✅ 主管查詢自己管理的員工
     */
    public function getMyEmployees(): JsonResponse
    {
        $user = Auth::user();
        $employees = $this->employeeService->getEmployeesByManager($user->id);

        if ($employees->isEmpty()) {
            return response()->json(['error' => '你沒有管理任何員工'], 403);
        }

        return response()->json($employees);
    }
}

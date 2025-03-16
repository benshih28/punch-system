<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\DB;
use App\Services\LeaveBalanceService;

class EmployeeController extends Controller
{
    protected $leaveBalanceService;

    public function __construct(LeaveBalanceService $leaveBalanceService)
    {
        $this->leaveBalanceService = $leaveBalanceService;
    }
    /**
     * @OA\Get(
     *     path="/api/employees",
     *     summary="取得所有員工列表（HR 介面）",
     *     description="HR 取得所有員工的資訊，包含部門、職位、員工姓名、主管、角色、狀態。",
     *     tags={"Employees"},
     *     security={{ "bearerAuth": {} }},
     *
     *     @OA\Parameter(
     *         name="department_id",
     *         in="query",
     *         description="篩選特定部門 ID 的員工",
     *         required=false,
     *         @OA\Schema(type="integer", example=2)
     *     ),
     *     @OA\Parameter(
     *         name="role_id",
     *         in="query",
     *         description="篩選特定角色 ID 的員工",
     *         required=false,
     *         @OA\Schema(type="integer", example=3)
     *     ),
     *     @OA\Parameter(
     *         name="user_id",
     *         in="query",
     *         description="篩選特定使用者 ID",
     *         required=false,
     *         @OA\Schema(type="integer", example=5)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="每頁顯示的筆數",
     *         required=false,
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="目前頁數",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="成功取得員工列表",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="成功獲取員工列表"),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="per_page", type="integer", example=10),
     *                 @OA\Property(property="total", type="integer", example=50),
     *                 @OA\Property(property="last_page", type="integer", example=5)
     *             ),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="department", type="string", example="IT 部門"),
     *                     @OA\Property(property="position", type="string", example="軟體工程師"),
     *                     @OA\Property(property="employee_name", type="string", example="ben"),
     *                     @OA\Property(property="manager_name", type="string", example="Alice Wang"),
     *                     @OA\Property(property="roles", type="array",
     *                         @OA\Items(type="string", example="員工")
     *                     ),
     *                     @OA\Property(property="status", type="string", enum={"pending", "approved", "rejected", "inactive"}, example="approved")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="未授權，請提供有效 Token"),
     *     @OA\Response(response=403, description="沒有權限存取"),
     *     @OA\Response(response=500, description="伺服器錯誤")
     * )
     */
    public function index(Request $request) // 取得所有員工列表（HR 介面）
    {
        $departmentId = $request->query('department_id', null);
        $roleId = $request->query('role_id', null);
        $userId = $request->query('user_id', null);
        $perPage = (int) $request->query('per_page', 10);
        $page = (int) $request->query('page', 1);
        $offset = ($page - 1) * $perPage;

        // **查詢總數**
        $totalEmployees = DB::selectOne('CALL CountEmployees(?, ?, ?)', [
            $departmentId,
            $roleId,
            $userId
        ])->total;

        // **查詢員工資料**
        $employees = DB::select('CALL GetEmployees(?, ?, ?, ?, ?)', [
            $departmentId,
            $roleId,
            $userId,
            $perPage,
            $offset
        ]);

        return response()->json([
            'message' => '成功獲取員工列表',
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $totalEmployees,
                'last_page' => ceil($totalEmployees / $perPage),
            ],
            'data' => $employees
        ], 200);
    }



    /**
     * @OA\Post(
     *     path="/api/employees",
     *     summary="HR 註冊新員工",
     *     description="HR 註冊新員工，會建立 `User` 帳號並在 `Employee` 記錄中標記 `pending` 狀態。HR 可選擇設定 `start_date` (入職日期)，否則為 NULL。",
     *     operationId="registerEmployeeByHR",
     *     tags={"Employees"},
     *     security={{"bearerAuth": {}}}, 
     * 
     *     @OA\RequestBody(
     *         required=true,
     *         description="HR 註冊新員工資訊",
     *         @OA\JsonContent(
     *             required={"name", "email", "password", "password_confirmation", "gender"},
     *             @OA\Property(property="name", type="string", example="John Doe", description="員工姓名"),
     *             @OA\Property(property="email", type="string", format="email", example="john.doe@example.com", description="員工電子郵件"),
     *             @OA\Property(property="password", type="string", example="Password123!", description="密碼"),
     *             @OA\Property(property="password_confirmation", type="string", example="Password123!", description="確認密碼"),
     *             @OA\Property(property="gender", type="string", enum={"male", "female"}, example="male", description="性別"),
     *             @OA\Property(property="start_date", type="string", format="date", example="2024-03-10", description="(選填) 入職日期，若未提供則為 NULL")
     *         )
     *     ),
     * 
     *     @OA\Response(
     *         response=201,
     *         description="員工註冊成功，等待 HR 審核",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="員工已註冊，等待審核"),
     *             @OA\Property(property="user", type="object", description="使用者資訊"),
     *             @OA\Property(property="employee", type="object",
     *                 @OA\Property(property="user_id", type="integer", example=5, description="對應 `users` 表的 ID"),
     *                 @OA\Property(property="status", type="string", example="pending", description="員工目前狀態"),
     *                 @OA\Property(property="start_date", type="string", format="date", example="2024-03-10", description="入職日期，可能為 NULL")
     *             )
     *         )
     *     ),
     * 
     *     @OA\Response(
     *         response=422,
     *         description="驗證失敗",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="請確認欄位格式是否正確")
     *         )
     *     ),
     * 
     *     @OA\Response(
     *         response=403,
     *         description="權限不足",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="權限不足，僅限 HR 操作")
     *         )
     *     )
     * )
     */
    public function store(Request $request)         // HR 註冊新員工   
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', Password::min(8)->letters()->numbers()->mixedCase()->symbols(), 'confirmed'],
            'gender' => ['required', 'in:male,female'],
            'start_date' => ['nullable', 'date'], // 新增 `start_date` 驗證
        ]);

        // **建立 `User` 帳號**
        $user = User::create([
            'name' => $request->name,
            'email' => strtolower($request->email),
            'password' => Hash::make($request->password),
            'gender' => $request->gender,
        ]);

        // **建立 `Employee`，並標記 `pending`**
        $employee = Employee::create([
            'user_id' => $user->id,
            'status' => 'pending',
            'start_date' => $request->start_date, // HR 可選擇設定入職日，否則為 NULL
        ]);

        return response()->json([
            'message' => '員工已註冊，等待審核',
            'user' => $user,
            'employee' => $employee,
        ], 201);
    }

    /**
     * @OA\Patch(
     *     path="/api/employees/{id}/review",
     *     summary="HR 批准 / 拒絕 員工註冊",
     *     description="HR 可以批准或拒絕員工註冊申請，批准後員工可正式入職，且第一次批准時會自動設定 `start_date`。",
     *     operationId="reviewEmployee",
     *     tags={"Employees"},
     *     security={{"bearerAuth": {}}}, 
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="員工的 ID",
     *         @OA\Schema(type="integer", example=3)
     *     ),
     *     
     *     @OA\RequestBody(
     *         required=true,
     *         description="選擇批准或拒絕員工註冊",
     *         @OA\JsonContent(
     *             required={"status"},
     *             @OA\Property(
     *                 property="status", 
     *                 type="string", 
     *                 enum={"approved", "rejected"}, 
     *                 example="approved", 
     *                 description="批准 (approved) 或 拒絕 (rejected)"
     *             )
     *         )
     *     ),
     * 
     *     @OA\Response(
     *         response=201,
     *         description="審核成功",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="員工已批准"),
     *             @OA\Property(property="start_date", type="string", format="date", example="2024-03-10", description="員工正式入職日期")
     *         )
     *     ),
     * 
     *     @OA\Response(
     *         response=200,
     *         description="員工申請已拒絕",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="員工申請已拒絕")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="找不到員工",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="找不到員工")
     *         )
     *     ),
     * 
     *     @OA\Response(
     *         response=422,
     *         description="驗證失敗",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="請提供有效的 status 值 (approved 或 rejected)")
     *         )
     *     ),
     * 
     *     @OA\Response(
     *         response=403,
     *         description="權限不足",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="權限不足，僅限 HR 操作")
     *         )
     *     )
     * )
     */
    public function reviewEmployee(Request $request, $id) // HR 批准 / 拒絕 員工註冊
    {
        $request->validate([
            'status' => 'required|in:approved,rejected',
        ]);

        $employee = Employee::find($id);

        if (!$employee) {
            return response()->json(['error' => '找不到員工'], 404);
        }

        if ($request->status === 'approved') {
            $employee->status = 'approved';

            // 如果 `start_date` 為 NULL，則補上當天日期
            if (!$employee->start_date) {
                $employee->start_date = now()->toDateString();
            }

            $employee->save();

            //  員工審核通過後初始化請假餘額
            $this->leaveBalanceService->initializeLeaveBalances($employee);

            return response()->json([
                'message' => '員工已批准，入職日期為 ' . $employee->start_date,
                'start_date' => $employee->start_date,
            ], 200);
        } elseif ($request->status === 'rejected') {
            // 🔹 **不刪除員工，只是標記為 rejected**
            $employee->status = 'rejected';
            $employee->save();

            return response()->json(['message' => '員工申請已拒絕'], 200);
        }
    }
   

    /**
     * @OA\Patch(
     *     path="/api/employees/{id}/assign",
     *     summary="HR 分配部門、職位、主管、角色",
     *     description="HR 指派員工的部門、職位、主管和角色。員工必須已通過審核 (approved) 才能指派。",
     *     operationId="assignEmployeeDetails",
     *     tags={"Employees"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="員工的 ID",
     *         @OA\Schema(type="integer", example=3)
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         description="需要指派的部門、職位、主管、角色 ID",
     *         @OA\JsonContent(
     *             required={"department_id", "position_id", "manager_id", "role_id"},
     *             @OA\Property(property="department_id", type="integer", example=1, description="部門 ID"),
     *             @OA\Property(property="position_id", type="integer", example=2, description="職位 ID"),
     *             @OA\Property(property="manager_id", type="integer", example=5, description="主管的使用者 ID"),
     *             @OA\Property(property="role_id", type="integer", example=3, description="角色 ID")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="員工部門、職位、主管、角色已更新",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="員工部門、職位、主管、角色已更新")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=400,
     *         description="員工未通過審核，無法指派",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="無法指派，員工尚未通過審核")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="找不到員工",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="找不到員工")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="驗證失敗"
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="權限不足"
     *     )
     * )
     */
    public function assignEmployeeDetails(Request $request, $id)   // HR 分配部門、職位、主管、角色
    {
        $request->validate([
            'department_id' => 'required|exists:departments,id',
            'position_id' => 'required|exists:positions,id',
            'manager_id' => 'required|exists:users,id',
            'role_id' => 'required|exists:roles,id',
        ]);

        $employee = Employee::find($id);
        if (!$employee || $employee->status !== 'approved') {
            return response()->json(['error' => '無法指派，員工尚未通過審核'], 400);
        }

        // 🔹 呼叫 MySQL 預存程序 AssignEmployeeDetails
        DB::statement('CALL AssignEmployeeDetails(?, ?, ?, ?, ?)', [
            $id,
            $request->department_id,
            $request->position_id,
            $request->manager_id,
            $request->role_id
        ]);

        return response()->json([
            'message' => '員工部門、職位、主管、角色已更新'
        ], 200);
    }
    /**
     * @OA\Delete(
     *     path="/api/employees/{id}",
     *     summary="HR 刪除員工 (標記為離職)",
     *     description="HR 可以將員工標記為離職 (inactive)，而不是真正刪除資料。",
     *     operationId="deleteEmployee",
     *     tags={"Employees"},
     *     security={{ "bearerAuth":{} }},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="員工 ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="員工已標記為離職",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="員工已標記為離職")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="找不到員工",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="找不到員工")
     *         )
     *     )
     * )
     */
    public function destroy($id)    // HR 刪除員工
    {
        $employee = Employee::find($id);

        if (!$employee) {
            return response()->json(['error' => '找不到員工'], 404);
        }

        // 直接將狀態標記為 `inactive`
        $employee->status = 'inactive';
        $employee->save();

        return response()->json(['message' => '員工已標記為離職'], 200);
    }


    /**
     * @OA\Get(
     *     path="/api/my/employees",
     *     summary="取得主管管理的員工 ID",
     *     description="返回當前登入使用者作為主管時，所管理的員工 user_id 列表。",
     *     operationId="getMyEmployees",
     *     tags={"Employee"},
     *     security={{"bearerAuth":{}}}, 
     *     @OA\Response(
     *         response=200,
     *         description="成功獲取主管管理的員工 ID",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="成功獲取你管理的員工"),
     *             @OA\Property(
     *                 property="user_ids",
     *                 type="array",
     *                 @OA\Items(type="integer", example=10),
     *                 description="員工的 user_id 列表"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="當前使用者沒有管理任何員工",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="你沒有管理任何員工")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="未授權，Token 無效或未提供",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Unauthorized")
     *         )
     *     )
     * )
     */
    public function getMyEmployees() // 主管查詢自己管理的員工
    {
        $user = auth()->user();
        $employees = Employee::where('manager_id', $user->id)
            ->pluck('user_id'); // 只取出 user_id

        if ($employees->isEmpty()) {
            return response()->json(['error' => '你沒有管理任何員工'], 403);
        }

        return response()->json([
            'message' => '成功獲取你管理的員工',
            'user_ids' => $employees
        ]);
    }
}

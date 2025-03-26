<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

use Illuminate\Support\Facades\DB;
use App\Models\EmployeeProfile;


class EmployeeController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/employees",
     *     summary="取得所有員工列表（HR 介面）",
     *     description="HR 取得所有員工的資訊，包含部門、職位、員工姓名、主管 ID、主管姓名、角色、狀態、入職日期。",
     *     tags={"Employees"},
     *     security={{ "bearerAuth": {} }},
     *
     *     @OA\Parameter(
     *         name="department_id",
     *         in="query",
     *         description="篩選特定部門 ID 的員工",
     *         required=false,
     *         @OA\Schema(type="integer", example=6669)
     *     ),
     *     @OA\Parameter(
     *         name="position_id", 
     *         in="query",
     *         description="篩選特定職位 ID 的員工",
     *         required=false,
     *         @OA\Schema(type="integer", example=66)
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
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="manager_id", type="integer", example=2, nullable=true),
     *                     @OA\Property(property="department", type="string", example="6669"),
     *                     @OA\Property(property="position", type="string", example="66"),
     *                     @OA\Property(property="employee_name", type="string", example="ben"),
     *                     @OA\Property(property="manager_name", type="string", example="John", nullable=true),
     *                     @OA\Property(property="roles", type="string", example="5551"),
     *                     @OA\Property(property="status", type="string", enum={"pending", "approved", "rejected", "inactive"}, example="approved"),
     *                     @OA\Property(property="hire_date", type="string", format="date", example="2025-03-19", nullable=true)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="未授權，請提供有效 Token"),
     *     @OA\Response(response=403, description="沒有權限存取"),
     *     @OA\Response(response=500, description="伺服器錯誤")
     * )
     */
    public function index(Request $request)
    {
        $departmentId = $request->input('department_id');
        $positionId = $request->input('position_id');
        $userId = $request->input('user_id');
        $page = $request->input('page', 1);
        $perPage = $request->input('per_page', 10);
        $offset = ($page - 1) * $perPage;
    
        // 執行預存程序
        $results = DB::select(
            'CALL GetEmployees(?, ?, ?, ?, ?)',
            [$departmentId, $positionId, $userId, $perPage, $offset]
        );
    
        // 提取資料和元資料
        $employees = [];
        $total = 0;
        $lastPage = 1;
        if (!empty($results)) {
            foreach ($results as $result) {
                $total = $result->total;
                $lastPage = $result->last_page;
                // 移除 total 和 last_page 欄位，避免出現在員工資料中
                unset($result->total);
                unset($result->last_page);
                $employees[] = $result;
            }
        }
    
        // 添加日誌
        \Log::info('GetEmployees result', [
            'total' => $total,
            'last_page' => $lastPage,
            'employees_count' => count($employees),
        ]);
    
        return response()->json([
            'data' => $employees,
            'meta' => [
                'total' => $total,
                'last_page' => $lastPage,
            ],
        ]);
    }


    /**
     * @OA\Post(
     *     path="/api/employees",
     *     summary="HR 註冊新員工",
     *     description="HR 註冊新員工，會建立 `User` 帳號並在 `Employee` 記錄中標記 `pending` 狀態。",
     *     operationId="registerEmployeeByHR",
     *     tags={"Employees"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="HR 註冊新員工資訊",
     *         @OA\JsonContent(
     *             required={"name", "email", "password", "password_confirmation", "gender"},
     *             @OA\Property(property="name", type="string", example="John Doe", description="員工姓名"),
     *             @OA\Property(property="email", type="string", format="email", example="john.doe@example.com", description="員工電子郵件"),
     *             @OA\Property(property="password", type="string", example="Password123!", description="密碼"),
     *             @OA\Property(property="password_confirmation", type="string", example="Password123!", description="確認密碼"),
     *             @OA\Property(property="gender", type="string", enum={"male", "female"}, example="male", description="性別")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="員工註冊成功，等待 HR 審核",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="員工已註冊，等待審核"),
     *             @OA\Property(property="user", type="object", description="使用者資訊"),
     *             @OA\Property(property="employee", type="object", description="員工資訊")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="驗證失敗"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="權限不足"
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
     *     description="HR 可以批准或拒絕員工註冊申請。",
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
     *             @OA\Property(property="status", type="string", enum={"approved", "rejected"}, example="approved", description="批准或拒絕")
     *         )
     *     ),
     * 
     *     @OA\Response(
     *         response=200,
     *         description="操作成功",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="員工已批准")
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
            $employee->save();

            return response()->json(['message' => '員工已批准'], 200);
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
     *     summary="HR 分配部門、職位、主管、角色，並設定入職日期",
     *     description="HR 指派員工的部門、職位、主管和角色，並記錄入職日期。員工必須已通過審核 (approved) 才能指派。",
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
     *         description="需要指派的部門、職位、主管、角色 ID 和入職日期",
     *         @OA\JsonContent(
     *             required={"department_id", "position_id", "manager_id", "role_id", "hire_date"},
     *             @OA\Property(property="department_id", type="integer", example=1, description="部門 ID"),
     *             @OA\Property(property="position_id", type="integer", example=2, description="職位 ID"),
     *             @OA\Property(property="manager_id", type="integer", example=5, description="主管的使用者 ID"),
     *             @OA\Property(property="role_id", type="integer", example=3, description="角色 ID"),
     *             @OA\Property(property="hire_date", type="string", format="date", example="2023-05-15", description="員工入職日期 (YYYY-MM-DD)")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="員工部門、職位、主管、角色已更新，並設定入職日期",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="員工部門、職位、主管、角色已更新，並設定入職日期"),
     *             @OA\Property(property="hire_date", type="string", format="date", example="2023-05-15")
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
            'hire_date' => 'required|date|before_or_equal:today'
        ]);

        $employee = Employee::find($id);
        if (!$employee || $employee->status !== 'approved') {
            return response()->json(['error' => '無法指派，員工尚未通過審核'], 400);
        }

        // 呼叫 MySQL 預存程序 AssignEmployeeDetails
        DB::statement('CALL AssignEmployeeDetails(?, ?, ?, ?, ?)', [
            $id,
            $request->department_id,
            $request->position_id,
            $request->manager_id,
            $request->role_id
        ]);


        // 更新或建立 employee_profiles 的 hire_date
        $employeeProfile = EmployeeProfile::updateOrCreate(
            ['employee_id' => $id], // 依據 employee_id 搜尋
            ['hire_date' => $request->hire_date] // 更新 hire_date
        );

        return response()->json([
            'message' => '員工部門、職位、主管、角色已更新，並設定入職日期',
            'hire_date' => $employeeProfile->hire_date
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

    /**
     * @OA\Get(
     *     path="/api/employees/approved",
     *     summary="取得已審核的員工列表（用於主管選項）",
     *     description="獲取所有狀態為 approved 的員工，作為主管選項使用。",
     *     tags={"Employees"},
     *     security={{ "bearerAuth": {} }},
     *
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="每頁顯示的筆數",
     *         required=false,
     *         @OA\Schema(type="integer", example=100)
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
     *         description="成功取得已審核的員工列表",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="成功獲取已審核員工列表"),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="per_page", type="integer", example=100),
     *                 @OA\Property(property="total", type="integer", example=50),
     *                 @OA\Property(property="last_page", type="integer", example=1)
     *             ),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="employee_name", type="string", example="John Doe"),
     *                     @OA\Property(property="department", type="string", example="IT 部門"),
     *                     @OA\Property(property="position", type="string", example="軟體工程師"),
     *                     @OA\Property(property="status", type="string", example="approved")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="未授權，請提供有效 Token"),
     *     @OA\Response(response=403, description="沒有權限存取"),
     *     @OA\Response(response=500, description="伺服器錯誤")
     * )
     */
    public function getApprovedEmployees(Request $request)
    {
        $perPage = $request->query('per_page', 100); // 預設每頁 100 筆，足以應付大多數主管選項需求
        $page = $request->query('page', 1);
        $offset = ($page - 1) * $perPage;

        // 計算總數
        $totalQuery = DB::select('CALL GetApprovedEmployeesCount()');
        $total = $totalQuery[0]->total;

        // 獲取已審核的員工
        $employees = DB::select('CALL GetApprovedEmployees(?, ?)', [
            $perPage,
            $offset
        ]);

        return response()->json([
            'message' => '成功獲取已審核員工列表',
            'meta' => [
                'current_page' => (int) $page,
                'per_page' => (int) $perPage,
                'total' => (int) $total,
                'last_page' => ceil($total / $perPage),
            ],
            'data' => $employees
        ], 200);
    }
}

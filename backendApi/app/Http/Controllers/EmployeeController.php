<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\DB;


class EmployeeController extends Controller
{
    // ✅ 取得所有員工列表（HR 介面）
    public function index()
    {
        return response()->json(Employee::all(), 200);
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
    public function reviewEmployee(Request $request, $id)// HR 批准 / 拒絕 員工註冊
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


    public function getEmployeeManager($id) // 員工查詢自己的主管
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

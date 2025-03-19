<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use App\Models\File;
use App\Http\Controllers\FileController;
// use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Models\PunchIn;
use App\Models\PunchOut;
use App\Models\PunchCorrection;
use App\Models\Employee;
use App\Models\Leave;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;


/**
 * @OA\Post(
 *     path="/api/user/update/profile",
 *     summary="更新使用者個人資料",
 *     description="使用者登入後，可選擇修改密碼或上傳大頭貼",
 *     tags={"User"},
 *     security={{"bearerAuth":{}}},
 *     
 *     @OA\RequestBody(
 *         required=false,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(
 *                 type="object",
 *                 @OA\Property(property="old_password", type="string", nullable=true, example="OldPass123!", description="舊密碼 (修改密碼時必填)"),
 *                 @OA\Property(property="new_password", type="string", nullable=true, example="NewPass456!", description="新密碼 (修改密碼時必填)"),
 *                 @OA\Property(property="new_password_confirmation", type="string", nullable=true, example="NewPass456!", description="確認新密碼 (修改密碼時必填)"),
 *                 @OA\Property(property="avatar", type="string", format="binary", nullable=true, description="大頭貼 (可單獨上傳，不影響密碼)")
 *             )
 *         )
 *     ),
 *     
 *     
 *     @OA\Response(
 *         response=200,
 *         description="個人資料已更新",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="個人資料已更新"),
 *             @OA\Property(property="avatar", type="string", example="avatar_1.279f7c1cb3782406ae8f1c8fcfb16257ea04a63fe1d5e2c0050aaa197f690309")
 *         )
 *     ),
 *     
 *     @OA\Response(
 *         response=400,
 *         description="新密碼輸入不一致、密碼輸入不符規則",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(
 *                 property="errors",
 *                 type="array",
 *                 @OA\Items(type="string"),
 *                 example={"新密碼與確認密碼不一致", "新密碼至少需要 8 個字元(包含大小寫字母、數字和特殊符號)"}
 *             )
 *         )
 *     ),
 *     
 *     @OA\Response(
 *         response=404,
 *         description="舊密碼錯誤或新密碼與舊密碼相同",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(
 *                 property="errors",
 *                 type="array",
 *                 @OA\Items(type="string"),
 *                 example={"舊密碼錯誤", "新密碼不能與舊密碼相同"}
 *             )
 *         )
 *     )
 * )
 */
class UserController extends Controller
{
    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        // 驗證輸入
        $validator = Validator::make($request->all(), [
            'old_password' => 'nullable',
            'new_password' => [
                'nullable',
                'confirmed',
                Password::min(8),
                'regex:/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[!#$%&*+\-.\/]).{8,}$/'
            ],
            'new_password_confirmation' => 'required_with:new_password',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ], [
            //檢查新密碼跟確認新密碼是否相符
            'new_password.confirmed' => '新密碼與確認新密碼不一致',
            'new_password.min' => '新密碼至少需要 8 個字元(包含大小寫字母、數字和特殊符號)',
            'new_password.regex' => '新密碼至少需要 8 個字元(包含大小寫字母、數字和特殊符號)',
            'new_password_confirmation.required_with' => '請輸入確認密碼'
        ]);

        // 如果驗證失敗，回傳 400 Bad Request
        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first()
            ], 400);
        }

        // 新密碼
        //當輸入了舊密碼，強制要求新密碼
        if ($request->filled('old_password')) {
            if (!$request->filled('new_password') || !$request->filled('new_password_confirmation')) {
                return response()->json(['message' => '未填寫新密碼或確認密碼'], 400);
            }
            // 1.檢查是否輸入舊密碼
            if (!$request->filled('old_password') || !Hash::check($request->old_password, $user->password)) {
                return response()->json(['message' => '舊密碼錯誤'], 404);
            }
            // 2.防止新密碼與舊密碼相同
            if (Hash::check($request->new_password, $user->password)) {
                return response()->json(['message' => '新密碼不能與舊密碼相同'], 404);
            }
            // 3.更新密碼
            $user->password = Hash::make($request->new_password);
        }
        // 防止沒輸入舊密碼，卻輸入新密碼
        if (!$request->filled('old_password') && ($request->filled('new_password') || $request->filled('new_password_confirmation'))) {
            return response()->json(['message' => '請輸入舊密碼才能變更密碼'], 400);
        }

        // Debug 記錄
        // Log::info('User profile updated: ' . $user->id);

        // 透過 FileController 上傳大頭貼

        // 如果有上傳新大頭貼，直接呼叫 FileController@uploadAvatar 方法
        if ($request->hasFile('avatar')) {
            $fileController = app(FileController::class);
            $avatarResponse = $fileController->uploadAvatar($request);
            $avatarData = json_decode($avatarResponse->getContent(), true);
            $avatarUrl = $avatarData['url'] ?? null;
        } else {
            // 從 files 表獲取當前最新的大頭貼 URL
            $file = File::where('user_id', $user->id)->whereNotNull('avatar')->first();
            $avatarUrl = $file ? Storage::url("avatars/" . $file->avatar) : null;
        }

        // 儲存變更
        $user->save();

        return response()->json(['message' => '個人資料已更新', 'avatar' => $avatarUrl]);

    }


    /**
     * @OA\Get(
     *     path="/api/user/details",
     *     summary="獲取使用者完整資訊",
     *     description="取得使用者的基本資料、打卡紀錄、請假資訊、角色與權限等資訊",
     *     tags={"User"},
     *     security={{"bearerAuth":{}}}, 
     *     
     *     @OA\Response(
     *         response=200,
     *         description="成功獲取使用者資訊",
     *         @OA\JsonContent(
     *             @OA\Property(property="user", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="王小明"),
     *                 @OA\Property(property="gender", type="string", example="male"),
     *                 @OA\Property(property="avatar", type="string", example="/storage/avatars/user_1.jpg"),
     *                 @OA\Property(property="position", type="string", example="工程師"),
     *                 @OA\Property(property="department_id", type="integer", example=2),
     *                 @OA\Property(property="department_name", type="string", example="技術部"),
     *                 @OA\Property(property="manager_id", type="integer", example=5),
     *                 @OA\Property(property="manager_name", type="string", example="張主管"),
     *                 @OA\Property(property="employee_status", type="string", example="approved")
     *             ),
     *             @OA\Property(property="punch_records", type="object",
     *                 @OA\Property(property="punch_in", type="string", format="date-time", example="2025-03-19 08:30:00"),
     *                 @OA\Property(property="punch_out", type="string", format="date-time", example="2025-03-19 18:00:00"),
     *                 @OA\Property(property="corrections", type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=10),
     *                         @OA\Property(property="correction_type", type="string", example="punch_out"),
     *                         @OA\Property(property="punch_time", type="string", format="date-time", example="2025-03-19 18:10:00"),
     *                         @OA\Property(property="status", type="string", example="approved")
     *                     )
     *                 )
     *             ),
     *             @OA\Property(property="roles_permissions", type="object",
     *                 @OA\Property(property="roles", type="array", @OA\Items(type="string", example="employee")),
     *                 @OA\Property(property="permissions", type="array", @OA\Items(type="string", example="view_attendance"))
     *             ),
     *             @OA\Property(property="recent_leaves", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=15),
     *                     @OA\Property(property="leave_type", type="string", example="年假"),
     *                     @OA\Property(property="start_time", type="string", format="date-time", example="2025-03-10 09:00:00"),
     *                     @OA\Property(property="end_time", type="string", format="date-time", example="2025-03-11 18:00:00"),
     *                     @OA\Property(property="status", type="string", example="approved")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function getUserDetails()
    {
        $user = Auth::user();

        // 取得頭像與職位
        $file = File::where('user_id', $user->id)
            ->whereNotNull('avatar')
            ->first();
        $avatar = $file ? Storage::url("avatars/" . $file->avatar) : null;

        $employee = Employee::where('user_id', $user->id)->first();

        // 取得當天打卡與補登資料
        $today = now()->format('Y-m-d');
        $punchIn = PunchIn::where('user_id', $user->id)->whereDate('timestamp', $today)->first();
        $punchOut = PunchOut::where('user_id', $user->id)->whereDate('timestamp', $today)->first();
        $punchCorrections = PunchCorrection::where('user_id', $user->id)
            ->whereDate('punch_time', $today)
            ->where('status', 'approved')
            ->get(['id', 'correction_type', 'punch_time', 'status']);

        // 取得部門、主管與員工狀態
        $department = $employee->department ?? null;
        $manager = $employee->manager ?? null;

        // 取得角色與權限
        $roles = $user->getRoleNames();
        $permissions = $user->getAllPermissions()->pluck('name');

        // 取得最近五筆請假審核狀態
        $recentLeaves = Leave::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get(['id', 'leave_type_id', 'start_time', 'end_time', 'status']);

        $recentLeaves->map(function ($leave) {
            $leave->leave_type = $leave->leaveType->name;
            unset($leave->leave_type_id);
            return $leave;
        });

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'gender' => $user->gender,
                'avatar' => $avatar,
                'position' => $employee->position->name ?? null,
                'department_id' => $department->id ?? null,
                'department_name' => $department->name ?? null,
                'manager_id' => $manager->id ?? null,
                'manager_name' => $manager->name ?? null,
                'employee_status' => $employee->status ?? null
            ],
            'punch_records' => [
                'punch_in' => $punchIn->timestamp ?? null,
                'punch_out' => $punchOut->timestamp ?? null,
                'corrections' => $punchCorrections
            ],
            'roles_permissions' => [
                'roles' => $roles,
                'permissions' => $permissions
            ],
            'recent_leaves' => $recentLeaves
        ]);
    }
}


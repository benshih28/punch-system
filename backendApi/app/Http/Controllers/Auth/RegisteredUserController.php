<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Employee;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class RegisteredUserController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/register",
     *     summary="使用者註冊",
     *     description="用戶註冊新帳號",
     *     operationId="registerUser",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="使用者註冊資訊",
     *         @OA\JsonContent(
     *             required={"name", "email", "password", "password_confirmation", "gender"},
     *             @OA\Property(property="name", type="string", example="John Doe", description="使用者名稱"),
     *             @OA\Property(property="email", type="string", format="email", example="john.doe@example.com", description="電子郵件"),
     *             @OA\Property(property="password", type="string", example="Password123!", description="密碼"),
     *             @OA\Property(property="password_confirmation", type="string", example="Password123!", description="確認密碼"),
     *             @OA\Property(property="gender", type="string", enum={"male", "female"}, example="male", description="性別")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="註冊成功",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="User successfully registered. Please log in."),
     *             @OA\Property(property="user", type="object", description="使用者資訊"),
     *             @OA\Property(property="employee", type="object", description="員工資訊")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="驗證失敗"
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {

        $request->merge([
            'email' => strtolower($request->email)
        ]);


        $request->validate([
            //必填 (required) 必須是字串 (string) 最大長度 255 個字元 (max:255)
            'name' => ['required', 'string', 'max:255'],
            // 必填 (required) 必須是字串 (string) 轉小寫 (lowercase) 格式必須是 Email (email) 最大長度 255 個字元 (max:255) 必須是唯一 Email (unique:users)
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users'],
            //必填 (required) 需要輸入 password_confirmation 欄位，確認密碼是否一致 (confirmed) 至少包含一個字母 & 一個數字 & 一個大寫字母和一個小寫字母 & 一個特殊符號
            'password' => ['required', 'string', Password::min(8)->letters()->numbers()->mixedCase()->symbols(), 'confirmed'],

            'gender' => ['required', 'in:male,female'], // 限制只能是 male 或 female
        ]);


        // 創建使用者
        //  修正：存入 gender
        $user = User::create([
            'name' => $request->name,
            'email' => strtolower($request->email),
            'password' => Hash::make($request->password),
            'gender' => $request->gender, //  確保性別存入
        ]);



        // 🔹 自動建立員工資料（`pending` 狀態，等待 HR 審核）
        $employee = Employee::create([
            'user_id' => $user->id,
            'status' => 'pending', // 預設狀態為待審核
        ]);

        event(new Registered($user));

        return response()->json([
            'message' => 'User successfully registered. Please log in.',
            'user' => $user,
            'employee' => $employee,
        ], 201);
    }
}


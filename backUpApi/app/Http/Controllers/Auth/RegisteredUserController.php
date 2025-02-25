<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class RegisteredUserController extends Controller
{
    /**
     * Handle an incoming registration request.
     */
    public function store(Request $request): JsonResponse
    {
        // 確保 Email 為小寫，避免重複
        $request->merge([
            'email' => strtolower($request->email)
        ]);

        // 驗證使用者輸入
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
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // 觸發 Laravel 註冊事件（可選）
        event(new Registered($user));

        return response()->json([
            'message' => 'User successfully registered. Please log in.',
        ], 201);
    }
}

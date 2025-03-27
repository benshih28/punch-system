<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Employee;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;


class SocialLoginController extends Controller
{
    public function handleGoogleLogin(Request $request)
    {
        $token = $request->input('access_token');

        try {
            // 使用 token 取得 Google 資料
            $googleUser = Socialite::driver('google')->stateless()->userFromToken($token);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Google login failed', 'error' => $e->getMessage()], 401);
        }

        $email = strtolower($googleUser->getEmail());

        // 檢查使用者是否已存在
        $user = User::where('email', $email)->first();

        if (!$user) {
            $user = User::create([
                'name' => $googleUser->getName(),
                'email' => $email,
                'password' => Hash::make(Str::random(12)), // 產生隨機密碼
                'gender' => 'male', // 你可以根據需求從前端送或這裡預設
            ]);

            // 建立 Employee 資料
            Employee::create([
                'user_id' => $user->id,
                'status' => 'pending',
            ]);
        }

        // 產生 JWT Token
        $jwtToken = JWTAuth::fromUser($user);

        return response()->json([
            'message' => 'Login with Google successful',
            'token' => $jwtToken,
            'user' => $user,
        ]);
    }
}

<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth; // ⚠️ 改為新的 JWTAuth
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;

class AuthenticatedSessionController extends Controller
{
    /**
     * 使用 JWT 進行登入，回傳 Token
     */
    public function store(Request $request)
    {
        // 驗證輸入
        $credentials = $request->only('email', 'password');

        // 嘗試登入，取得 JWT Token
        if (!$token = JWTAuth::attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // **每次登入時先作廢舊 Token**
        JWTAuth::invalidate(JWTAuth::getToken());



        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => JWTAuth::factory()->getTTL() * 60, // Token 逾期時間（秒）
            'user' =>JWTAuth::user(), // 確保正確獲取使用者
        ]);
    }

    /**
     * 使用者登出（讓 Token 失效）
     */
    public function destroy(Request $request)
    {
        try {
            // 確保獲取當前 Token
            $token = JWTAuth::getToken();
    
            if (!$token) {
                return response()->json(['error' => 'Token not provided'], 400);
            }
    
            // 讓 Token 失效
            JWTAuth::invalidate($token);
    
            return response()->json(['message' => 'Successfully logged out']);
        } catch (\PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException $e) {
            // Token 過期時仍允許登出
            return response()->json(['message' => 'Token has expired, but logout success'], 200);
        } catch (\PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException $e) {
            return response()->json(['error' => 'Failed to log out'], 500);
        }
    }
}

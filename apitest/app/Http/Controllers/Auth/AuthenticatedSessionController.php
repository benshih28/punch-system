<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class AuthenticatedSessionController extends Controller
{
    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request)
    {
        // 確保使用者登入
        $request->authenticate();

        $user = Auth::user();

        // 產生 Bearer Token
        $token = $user->createToken('API Token')->plainTextToken;

        return response()->json([
            'message' => '登入成功',
            'user' => $user,
            'token' => $token
        ], 200);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request)
    {
        $user = $request->user();

        if ($user) {
            // 🔹 刪除所有 Token，讓這個使用者的 API Token 失效
            $user->tokens()->delete();
        }
    
        return response()->json([
            'message' => 'Logged out successfully'
        ], 200);
    }
}

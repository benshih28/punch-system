<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth; //假如Laravel版本太新

class AuthenticatedSessionController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/login",
     *     summary="使用者登入",
     *     description="用戶使用電子郵件和密碼登入系統，成功後返回 JWT token",
     *     operationId="loginUser",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="登入憑證",
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *             @OA\Property(property="email", type="string", format="email", example="john.doe@example.com", description="使用者電子郵件"),
     *             @OA\Property(property="password", type="string", example="Password123!", description="使用者密碼")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="登入成功，返回 JWT token和使用者資訊",
     *         @OA\JsonContent(
     *             @OA\Property(property="access_token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...", description="JWT 存取token"),
     *             @OA\Property(property="token_type", type="string", example="bearer", description="token類型"),
     *             @OA\Property(property="expires_in", type="integer", example=3600, description="token過期時間（秒）"),
     *             @OA\Property(property="user", type="object", description="登入的使用者資訊",
     *                 @OA\Property(property="id", type="integer", example=1, description="使用者 ID"),
     *                 @OA\Property(property="name", type="string", example="John Doe", description="使用者名稱"),
     *                 @OA\Property(property="email", type="string", example="john.doe@example.com", description="電子郵件")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="認證失敗",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Unauthorized", description="錯誤訊息")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="伺服器錯誤，無法生成token",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Could not create token", description="錯誤訊息"),
     *             @OA\Property(property="message", type="string", example="詳細錯誤訊息", description="異常詳細信息")
     *         )
     *     )
     * )
     */
    public function store(Request $request)
    {
        // 驗證輸入
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        try {
            // 嘗試登入並取得 Token
            if (!$token = JWTAuth::attempt($credentials)) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            // 取得登入的使用者
            $user = JWTAuth::user();

            return response()->json([
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => auth()->factory()->getTTL() * 60,
                'user' => $user,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Could not create token', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/logout",
     *     summary="使用者登出",
     *     description="用戶登出系統，使當前 JWT token失效",
     *     operationId="logoutUser",
     *     tags={"Authentication"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="成功登出",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Successfully logged out", description="登出成功訊息")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="未提供token",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Token not provided", description="錯誤訊息")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="token已過期，但登出成功",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Token has expired, but logout success", description="登出成功訊息")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="登出失敗",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Failed to log out", description="錯誤訊息")
     *         )
     *     )
     * )
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

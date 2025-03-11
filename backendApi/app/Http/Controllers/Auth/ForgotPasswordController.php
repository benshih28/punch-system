<?php

namespace App\Http\Controllers\Auth; 

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\ForgotPasswordService;
use OpenApi\Annotations as OA;

class ForgotPasswordController extends Controller
{
    protected $forgotPasswordService;

    public function __construct(ForgotPasswordService $forgotPasswordService)
    {
        $this->forgotPasswordService = $forgotPasswordService;
    }


     /**
     * @OA\Post(
     *     path="/api/forgot/password",
     *     summary="忘記密碼",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email"},
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com", description="使用者電子郵件")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="新密碼已發送至您的信箱，請查收",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="新密碼已發送至您的信箱，請查收")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="該 Email 未註冊",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="該 Email 未註冊")
     *         )
     *     )
     * )
     */
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        return $this->forgotPasswordService->handleForgotPassword($request->email);
    }
}

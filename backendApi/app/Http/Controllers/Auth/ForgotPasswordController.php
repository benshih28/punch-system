<?php

namespace App\Http\Controllers\Auth; 

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\ForgotPasswordService;

class ForgotPasswordController extends Controller
{
    protected $forgotPasswordService;

    public function __construct(ForgotPasswordService $forgotPasswordService)
    {
        $this->forgotPasswordService = $forgotPasswordService;
    }

    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        return $this->forgotPasswordService->handleForgotPassword($request->email);
    }
}

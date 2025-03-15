<?php

namespace App\Services;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Models\User;
use App\Mail\ForgotPasswordMail;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Str;

class ForgotPasswordService
{
    public function handleForgotPassword($email)
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            return response()->json(['message' => '該 Email 未註冊'], 404);
        }

        // 產生符合密碼規則的 8 位數密碼
        $newPassword = $this->generateStrongPassword();

        // 產生新密碼
        $user->password = Hash::make($newPassword);
        $user->save();

        // 發送 Email
        Mail::to($user->email)->queue(new ForgotPasswordMail($newPassword));

        return response()->json(['message' => '新密碼已發送至您的信箱，請查收']);
    }

     /**
     * 產生符合安全規則的 8 位數密碼
     */
    private function generateStrongPassword()
    {
        // 確保密碼至少有 1 個大寫、1 個小寫、1 個數字、1 個特殊符號
        $uppercase = chr(rand(65, 90)); // A-Z
        $lowercase = chr(rand(97, 122)); // a-z
        $number = chr(rand(48, 57)); // 0-9
        $symbols = '!#$%&*+-./'; //特殊符號
        $symbol = $symbols[rand(0, strlen($symbols) - 1)]; // 隨機選擇一個特殊符號

        // 剩下的 4 個字元隨機生成
        $remaining = substr(str_shuffle(str_repeat('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789', 4)), 0, 4);

        // 組合密碼並打亂(共八位數)
        $password = str_shuffle($uppercase . $lowercase . $number . $symbol . $remaining);

        return $password;
    }

}
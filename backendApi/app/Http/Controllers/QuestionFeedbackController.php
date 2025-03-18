<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class QuestionFeedbackController extends Controller
{
    public function sendFeedback(Request $request)
    {
        // 驗證請求資料
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'issueType' => 'required|string',
            'message' => 'required|string',
        ]);

        // 測試是否收到請求
        Log::info('收到前端請求:', $validatedData);

        try {
            // 發送郵件
            Mail::send('emails.feedback', ['details' => $validatedData], function ($message) use ($validatedData) {
                $message->to('tarotworld1210@gmail.com') // 開發者信箱(收件人)
                    ->subject('使用者問題反饋 - ' . $validatedData['issueType'])
                    ->replyTo($validatedData['email'], $validatedData['name']); // 讓管理者回覆用戶信件
            });

            return response()->json([
                'success' => true,
                'message' => '問題反饋已成功發送'
            ], 200);
        } catch (\Exception $e) {
            Log::error('郵件發送失敗: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => '郵件發送失敗',
                // 'error' => $e->getMessage(), // 這行可以隱藏，避免洩漏錯誤細節
            ], 500);
        }
    }
}

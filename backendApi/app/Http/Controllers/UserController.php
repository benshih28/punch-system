<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use App\Models\File;
use App\Http\Controllers\FileController;
// use Illuminate\Support\Facades\Log;


class UserController extends Controller
{
    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        // 驗證輸入
        $request->validate([
            'old_password' => 'nullable',
            'new_password' => [
                'nullable',
                'confirmed',
                Password::min(8),
                'regex:/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[!#$%&*+\-.\/]).{8,}$/'
            ],
            'new_password_confirmation' => 'required_with:new_password',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ], [
            //檢查新密碼跟確認新密碼是否相符
            'new_password.confirmed' => '新密碼與確認新密碼不一致',
            'new_password.min' => '新密碼至少需要 8 個字元(包含大小寫字母、數字和特殊符號)',
            'new_password.regex' => '新密碼至少需要 8 個字元(包含大小寫字母、數字和特殊符號)',
            'new_password_confirmation.required_with' => '請輸入確認密碼'
        ]);

        // 新密碼
        if ($request->filled('new_password')) {
            // 1.檢查是否輸入舊密碼
            if (!$request->filled('old_password') || !Hash::check($request->old_password, $user->password)) {
                return response()->json(['message' => '舊密碼錯誤'], 403);
            }
            // 2.防止新密碼與舊密碼相同
            if (Hash::check($request->new_password, $user->password)) {
                return response()->json(['message' => '新密碼不能與舊密碼相同'], 403);
            }
            // 3.更新密碼
            $user->password = Hash::make($request->new_password);
        }

        // Debug 記錄
        // Log::info('User profile updated: ' . $user->id);

        // 透過 FileController 上傳大頭貼

        // 如果有上傳新大頭貼，直接呼叫 FileController@uploadAvatar 方法
        if ($request->hasFile('avatar')) {
            $fileController = app(FileController::class);
            $avatarResponse = $fileController->uploadAvatar($request);
            $avatarData = json_decode($avatarResponse->getContent(), true);
            $avatarUrl = $avatarData['url'] ?? null;
        } else {
            // 從 files 表獲取當前最新的大頭貼 URL
            $file = File::where('user_id', $user->id)->whereNotNull('avatar')->first();
            $avatarUrl = $file ? Storage::url("avatars/" . $file->avatar) : null;
        }

        // 儲存變更
        $user->save();

        return response()->json(['message' => '個人資料已更新', 'avatar' => $avatarUrl]);

    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

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
                Password::min(9),
                // ->mixedCase()->numbers()],
                'regex:/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[\W_]).{9,}$/'
            ],
            'new_password_confirmation' => 'required_with:new_password',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ], [
            //檢查新密碼跟確認新密碼是否相符
            'new_password.confirmed' => '新密碼與確認新密碼不一致',
            'new_password.min' => '新密碼至少需要 9 個字元(包含大小寫字母、數字和特殊符號)',
            'new_password.regex' => '新密碼至少需要 9 個字元(包含大小寫字母、數字和特殊符號)',
            'new_password_confirmation.required_with' => '請輸入確認密碼'
        ]);

   
        // 更新密碼(跟檢查舊密碼是否正確）
        if ($request->filled('new_password')) {
            if (!$request->filled('old_password') || !Hash::check($request->old_password, $user->password)) {
                return response()->json(['message' => '舊密碼錯誤'], 403);
            }
            $user->password = Hash::make($request->new_password);
        }

        // 處理大頭照上傳
        if ($request->hasFile('avatar')) {
            // 刪除舊大頭照
            if ($user->avatar) {
                Storage::delete('public/avatars/' . $user->avatar);
            }

            // 儲存新大頭照
            $avatarPath = $request->file('avatar')->store('public/avatars');
            $user->avatar = basename($avatarPath);
        }

        // 儲存變更
        $user->save();

        return response()->json(['message' => '個人資料已更新', 'avatar' => $user->avatar]);
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\File;
use Illuminate\Support\Facades\Storage;

class FileController extends Controller
{
    // 上傳大頭貼 avatar
    public function uploadAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        // 設定檔案名稱
        $filename = 'avatar_' . Auth::id() . '_' . time() . '.' . $request->file('avatar')->getClientOriginalExtension();

        // 存放到 `storage/app/public/avatars/`
        $path = $request->file('avatar')->storeAs('avatars', $filename, 'public');

        // 刪除舊大頭貼
        File::where('user_id',  Auth::id())->whereNull('leave_id')->delete();

        // 新增大頭貼記錄
        $file = File::create([
            'user_id' => Auth::id(),
            'avatar' => $filename // 只存 filename，不存 path
        ]);

        return response()->json([
            'message' => '大頭貼更新成功',
            'url' => Storage::url("avatars/" . $file->avatar)
        ]);
    }

    // 取得大頭貼
    public function getAvatar()
    {
        // $file = File::where('user_id', Auth::id())->first();
        $file = File::where('user_id', Auth::id())
                ->whereNotNull('avatar') // ✅ 確保 avatar 不是 NULL
                ->first();

        return response()->json([
            // 'avatar_url' => $file ? Storage::url("avatars/" . $file->avatar) : asset('default-avatar.png')
            'avatar_url' => $file && $file->avatar
                ? Storage::url("avatars/" . $file->avatar) // ✅ 存在則回傳檔案 URL
                // : asset('default-avatar.png') // ✅ 如果 `NULL`，則回傳預設圖片
                : null // ✅ 如果 `NULL`，則回傳 `NULL`
        ]);
    }



    // // 上傳請假附件 leave_attachment
    // public function uploadLeaveAttachment(Request $request, $leave_id)
    // {
    //     $request->validate([
    //         'leave_attachment' => 'required|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:2048',
    //     ]);

    //     // 設定檔案名稱
    //     $filename = 'leave_' . Auth::id() . '_' . time() . '.' . $request->file('leave_attachment')->getClientOriginalExtension();

    //     // 只使用一個 `$path`，確保存入 `storage/app/public/leave_attachments/`
    //     $path = $request->file('leave_attachment')->storeAs('leave_attachments', $filename, 'public');

    //     // 新增請假附件記錄
    //     $file = File::create([
    //         'user_id' => Auth::id(),
    //         'leave_id' => $leave_id,
    //         'leave_attachment' => $filename
    //     ]);

    //     return response()->json([
    //         'message' => '請假附件上傳成功',
    //         'url' => Storage::url("leave_attachments/" . $file->leave_attachment)
    //     ]);
    // }

}

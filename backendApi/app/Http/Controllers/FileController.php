<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\File;
use Illuminate\Support\Facades\Storage;
// use Illuminate\Support\Facades\Log;
// use App\Http\Controllers\Controller;


class FileController extends Controller
{

    /**
 * @OA\Get(
 *     path="/api/avatar",
 *     summary="取得使用者大頭貼",
 *     description="使用者先行登入後，取得使用者大頭貼",
 *     tags={"User"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(
 *         response=200,
 *         description="成功取得大頭貼",
 *         @OA\JsonContent(
 *             @OA\Property(property="avatar_url", type="string", nullable=true, example="/storage/avatars/avatar_2.6da43bda2dfa8d30688b2136cc26d91d6d1280bed7b6cb08cbc55b6baa66359e")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="沒有找到大頭貼",
 *         @OA\JsonContent(
 *             @OA\Property(property="avatar_url", type="string", nullable=true, example=null)
 *         )
 *     )
 * )
 */

    // 上傳大頭貼 avatar
    public function uploadAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpg,jpeg,png|max:2048',
        ]);


        // 取得上傳的副檔名
        // $extension = $request->file('avatar')->getClientOriginalExtension();
        $hash = hash('sha256', Auth::id() . time()); // 產生雜湊值
        $filename = 'avatar_' . Auth::id() . '.' . $hash;

        // 取得目前的 `avatar` 記錄
        $file = File::where('user_id', Auth::id())->whereNull('leave_id')->first();
        $oldFilename = $file ? $file->avatar : null;

        // 刪除舊的大頭貼
        if ($oldFilename && Storage::disk('public')->exists("avatars/{$oldFilename}")) {
            Storage::disk('public')->delete("avatars/{$oldFilename}");
        }

        // 存放到 storage/app/public/avatars/
        $path = $request->file('avatar')->storeAs('avatars', $filename, 'public');

        // ✅ 更新或新增大頭貼記錄
        File::updateOrCreate(
            ['user_id' => Auth::id(), 'leave_id' => null], // 搜尋條件
            ['avatar' => $filename] // 更新的值
        );

        // **新增 DEBUG LOG**
        // Log::info("Avatar uploaded: " . $filename);
        // Log::info("Storage URL: " . Storage::url("avatars/" . $filename));

        return response()->json([
            'message' => '大頭貼更新成功',
            'url' => Storage::url("avatars/" . $filename)

        ]);
    }

    // 取得大頭貼
    public function getAvatar()
    {
        // $file = File::where('user_id', Auth::id())->first();
        $file = File::where('user_id', Auth::id())

            ->whereNotNull('avatar') // 確保 avatar 不是 NULL
            ->first();


        return response()->json([
            // 'avatar_url' => $file ? Storage::url("avatars/" . $file->avatar) : asset('default-avatar.png')
            'avatar_url' => $file && $file->avatar
                ? Storage::url("avatars/" . $file->avatar) 
                : null // 如NULL，則回傳 NULL

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

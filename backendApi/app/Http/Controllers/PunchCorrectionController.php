<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PunchCorrection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\PunchOut;

class PunchCorrectionController extends Controller
{
    // 1️⃣ 提交補登請求
    public function store(Request $request)
    {
        // 驗證輸入
        $validatedData = $request->validate([
            'correction_type' => 'required|in:punch_in,punch_out',
            'punch_time' => 'required|date_format:Y-m-d H:i:s', // 確保格式正確
            'reason' => 'nullable|string',
        ]);

        // 取得當前登入的使用者
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => '未授權的請求'], 401);
        }

        // 確保 `punch_time` 不在未來
        $punchTime = Carbon::parse($validatedData['punch_time']);
        if ($punchTime->isFuture()) {
            return response()->json(['message' => '打卡時間不能是未來時間'], 400);
        }

        // 檢查是否已經有相同日期 & 類型的補登紀錄（避免重複申請）
        $existingCorrection = PunchCorrection::where('user_id', $user->id)
            ->whereDate('punch_time', $punchTime->toDateString()) // 只比對日期部分
            ->where('correction_type', $validatedData['correction_type'])
            ->where('status', 'pending') // 只檢查「待審核」的
            ->exists();

        if ($existingCorrection) {
            return response()->json(['message' => '已經有相同日期的補登申請，請勿重複申請'], 400);
        }

        // 儲存補登申請
        $punchCorrection = PunchCorrection::create([
            'user_id' => $user->id,
            'correction_type' => $validatedData['correction_type'],
            'punch_time' => $punchTime,
            'reason' => $validatedData['reason'],
            'status' => 'pending', // 預設狀態為「待審核」
        ]);

        return response()->json([
            'message' => '補登申請成功，等待審核',
            'data' => $punchCorrection
        ], 201);
    }



    // 2️⃣ 管理員審核（批准）
    public function approve(Request $request, $id)
    {
        $request->validate([
            'review_message' => 'nullable|string|max:255' // 允許管理員附加訊息
        ]);

        // 找到補登申請
        $correction = PunchCorrection::findOrFail($id);

        // 只允許審核 pending 狀態的補登
        if ($correction->status !== 'pending') {
            return response()->json(['message' => '此補登申請已被處理'], 400);
        }

        // 更新補登申請的狀態
        $correction->update([
            'status' => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'review_message' => $request->input('review_message'), // 儲存管理員的說明
        ]);

        // 如果補登的是上班打卡 (punch_in)
        if ($correction->correction_type === 'punch_in') {
            // 解析補登的 punch_time 並取得日期
            $punchDate = $correction->punch_time->toDateString();

            // 呼叫預存程序，更新當天最晚的 punch_out
            DB::statement('CALL ApprovePunchOut(?, ?)', [$correction->user_id, $punchDate]);
            dd('Stored Procedure Executed');

            return response()->json([
                'message' => '補登申請已通過，已更新當天最晚的下班打卡紀錄',
                'data' => $correction
            ]);
        }

        return response()->json([
            'message' => '補登已通過審核',
            'data' => $correction
        ], 200);
    }


    // 3️⃣ 管理員審核（拒絕）
    public function reject(Request $request, $id)
    {
        $request->validate([
            'review_message' => 'required|string|max:255' // 必須填寫拒絕原因
        ]);

        $correction = PunchCorrection::findOrFail($id);

        // 確保這筆補登還未審核
        if ($correction->status !== 'pending') {
            return response()->json(['message' => '此補登請求已審核，無法修改'], 400);
        }

        $correction->update([
            'status' => 'rejected',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'review_message' => $request->review_message, // 儲存管理員的說明
        ]);

        return response()->json([
            'message' => '補登請求已被拒絕',
            'data' => $correction
        ], 200);
    }


    public function getFinalAttendanceRecords(Request $request)
    {
        $userId = Auth::guard('api')->id();
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        if (!$startDate || !$endDate) {
            return response()->json(['error' => '請提供 start_date 和 end_date'], 400);
        }

        // 依序傳入 userId 7 次
        $records = DB::select('CALL GetFinalAttendanceRecords(?,?,?)', [
            $userId,
            $startDate,
            $endDate,
        ]);

        return response()->json($records);
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\PunchIn;
use App\Models\PunchOut;
use Carbon\Carbon;// ✅ 使用伺服器時間

class PunchController extends Controller
{
    /**
     * 📌 上班打卡（一天內限制一次）
     */
    public function punchIn(Request $request)
    {
        $user = Auth::guard('api')->user();
        $today = Carbon::now()->toDateString();

        // 檢查當日是否已有上班紀錄
        $existingPunchIn = PunchIn::where('user_id', $user->id)
            ->whereDate('timestamp', $today)
            ->first();

        if ($existingPunchIn) {
            return response()->json([
                'message'  => '今天已經完成上班打卡！',
                'punch_in' => [
                    'user_id'   => $existingPunchIn->user_id,
                    'timestamp' => $existingPunchIn->timestamp->format('Y-m-d H:i:s'),
                ],
            ], 400);
        }

        // 若無上班紀錄，則建立新的打卡記錄
        $punchIn = PunchIn::create([
            'user_id'   => $user->id,
            'timestamp' => Carbon::now(),
        ]);

        return response()->json([
            'message'  => 'Punch in recorded',
            'punch_in' => [
                'user_id'   => $punchIn->user_id,
                'timestamp' => $punchIn->timestamp->format('Y-m-d H:i:s'),
            ],
        ], 201);
    }

    /**
     * 📌 下班打卡
     */
    public function punchOut(Request $request)
    {
        $user        = Auth::guard('api')->user(); // ✅ 適用 JWT
        $currentTime = Carbon::now(); // ✅ 使用伺服器時間
        
        // 建立下班打卡，預設為有效
        $punchOut = PunchOut::create([
            'user_id'   => $user->id,
            'timestamp' => $currentTime,
            'is_valid'  => true,
        ]);
        
        // 取得當天最早的上班打卡紀錄
        $punchInRecord = PunchIn::where('user_id', $user->id)
            ->whereDate('timestamp', $currentTime->toDateString())
            ->orderBy('timestamp', 'asc')
            ->first();
        
        // 若找不到上班打卡或下班時間早於上班打卡，標記下班打卡為無效
        if (!$punchInRecord || $punchInRecord->timestamp > $currentTime) {
            $punchOut->update(['is_valid' => false]);
        }
        
        return response()->json([
            'message'   => 'Punch out recorded',
            'punch_out' => [
                'user_id'   => $punchOut->user_id,
                'timestamp' => $punchOut->timestamp->format('Y-m-d H:i:s'), // ✅ 確保格式正確
                'is_valid'  => $punchOut->is_valid, // ✅ 確保 `is_valid` 回傳
            ],
        ], 201);
    }


/**
 * 取得每日打卡記錄
 * 利用 UNION 取得該使用者所有出現過的日期，再以日期為單位左連接上班與下班的聚合結果，
 * 即使某天僅有下班卡，上班時間將回傳 NULL。
 */

    public function getAttendanceRecords()
    {
        $userId = Auth::guard('api')->id();
    
        // 直接呼叫 Stored Procedure
        $records = DB::select('CALL GetAttendanceRecords(?)', [$userId]);
    
        return response()->json($records);
    }
}


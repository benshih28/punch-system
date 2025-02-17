<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\PunchIn;
use App\Models\PunchOut;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    // 🚀 上班打卡（儲存到 punch_ins）
    public function punchIn(Request $request)
    {
        $user = Auth::user();

        // 確保今天還沒有上班打卡
        $alreadyPunchedIn = PunchIn::where('user_id', $user->id)
            ->whereDate('timestamp', Carbon::today())
            ->exists();

        if ($alreadyPunchedIn) {
            return response()->json(['message' => '今天已經打過上班卡'], 400);
        }

        // 新增上班打卡紀錄
        $punchIn = PunchIn::create([
            'user_id' => $user->id,
            'timestamp' => Carbon::now(),
        ]);

        return response()->json([
            'message' => '上班打卡成功',
            'attendance' => $punchIn
        ], 201);
    }

    // 🚀 下班打卡（儲存到 punch_outs）
    public function punchOut(Request $request)
    {
        $user = Auth::user();

        // 確保今天還沒有下班打卡
        $alreadyPunchedOut = PunchOut::where('user_id', $user->id)
            ->whereDate('timestamp', Carbon::today())
            ->exists();

        if ($alreadyPunchedOut) {
            return response()->json(['message' => '今天已經打過下班卡'], 400);
        }

        // 新增下班打卡紀錄
        $punchOut = PunchOut::create([
            'user_id' => $user->id,
            'timestamp' => Carbon::now(),
        ]);

        return response()->json([
            'message' => '下班打卡成功',
            'attendance' => $punchOut
        ], 201);
    }

    public function getAttendance()
    {
        $user = Auth::user();
    
        // 呼叫儲存過程
        $attendanceRecords = DB::select("CALL GetAttendance(?)", [$user->id]);
    
        return response()->json([
            'message' => '考勤紀錄查詢成功',
            'data' => $attendanceRecords
        ]);
    }
    
}

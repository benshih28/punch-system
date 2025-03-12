<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\PunchIn;
use App\Models\PunchOut;
use Carbon\Carbon; // ✅ 使用伺服器時間

class PunchController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/punch/in",
     *     summary="上班打卡",
     *     description="員工使用此 API 來進行上班打卡，每天只能打卡一次。",
     *     operationId="punchIn",
     *     tags={"Attendance"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=201,
     *         description="打卡成功",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Punch in recorded"),
     *             @OA\Property(property="punch_in", type="object",
     *                 @OA\Property(property="user_id", type="integer", example=1),
     *                 @OA\Property(property="timestamp", type="string", format="date-time", example="2025-03-12 08:30:00")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="今天已經完成上班打卡",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="今天已經完成上班打卡！"),
     *             @OA\Property(property="punch_in", type="object",
     *                 @OA\Property(property="user_id", type="integer", example=1),
     *                 @OA\Property(property="timestamp", type="string", format="date-time", example="2025-03-12 08:30:00")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="未授權，請重新登入",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthorized")
     *         )
     *     )
     * )
     */
    // 上班打卡（一天內限制一次）
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
                'message' => '今天已經完成上班打卡！',
                'punch_in' => [
                    'user_id' => $existingPunchIn->user_id,
                    'timestamp' => $existingPunchIn->timestamp->format('Y-m-d H:i:s'),
                ],
            ], 400);
        }

        // 若無上班紀錄，則建立新的打卡記錄
        $punchIn = PunchIn::create([
            'user_id' => $user->id,
            'timestamp' => Carbon::now(),
        ]);

        return response()->json([
            'message' => 'Punch in recorded',
            'punch_in' => [
                'user_id' => $punchIn->user_id,
                'timestamp' => $punchIn->timestamp->format('Y-m-d H:i:s'),
            ],
        ], 201);
    }
    /**
     * @OA\Post(
     *     path="/api/punch/out",
     *     summary="下班打卡",
     *     description="員工使用此 API 來進行下班打卡，系統會自動檢查當天是否已經有上班打卡紀錄。",
     *     operationId="punchOut",
     *     tags={"Attendance"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=201,
     *         description="打卡成功",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Punch out recorded"),
     *             @OA\Property(property="punch_out", type="object",
     *                 @OA\Property(property="user_id", type="integer", example=1),
     *                 @OA\Property(property="timestamp", type="string", format="date-time", example="2025-03-12 17:30:00"),
     *                 @OA\Property(property="is_valid", type="boolean", example=true, description="是否為有效打卡")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="無效的下班打卡，因為沒有對應的上班打卡紀錄",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="無效的下班打卡，請先上班打卡"),
     *             @OA\Property(property="punch_out", type="object",
     *                 @OA\Property(property="user_id", type="integer", example=1),
     *                 @OA\Property(property="timestamp", type="string", format="date-time", example="2025-03-12 17:30:00"),
     *                 @OA\Property(property="is_valid", type="boolean", example=false, description="無效打卡")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="未授權，請重新登入",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthorized")
     *         )
     *     )
     * )
     */
    // 下班打卡
    public function punchOut(Request $request)
    {
        $user = Auth::guard('api')->user(); // ✅ 適用 JWT
        $currentTime = Carbon::now(); // ✅ 使用伺服器時間

        // 建立下班打卡，預設為有效
        $punchOut = PunchOut::create([
            'user_id' => $user->id,
            'timestamp' => $currentTime,
            'is_valid' => true,
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
            'message' => 'Punch out recorded',
            'punch_out' => [
                'user_id' => $punchOut->user_id,
                'timestamp' => $punchOut->timestamp->format('Y-m-d H:i:s'), // ✅ 確保格式正確
                'is_valid' => $punchOut->is_valid, // ✅ 確保 `is_valid` 回傳
            ],
        ], 201);
    }


    /**
     * 取得每日打卡記錄
     * 利用 UNION 取得該使用者所有出現過的日期，再以日期為單位左連接上班與下班的聚合結果。
     */

    public function getFianlAttendanceRecords()
    {
        $userId = Auth::guard('api')->id();

        // 直接呼叫 Stored Procedure
        $records = DB::select('CALL GetFinalAttendanceRecords(?)', [$userId]);

        return response()->json($records);
    }
}

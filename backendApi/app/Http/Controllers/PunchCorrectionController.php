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
    // 強制回傳 JSON
    public function __construct()
    {
        request()->headers->set('Accept', 'application/json');
    }


    // 1️⃣ 提交補登請求
    public function store(Request $request)
    {
        // 取得當前登入的使用者
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => '未授權的請求'], 401);
        }

        // 驗證輸入
        $validatedData = $request->validate([
            'correction_type' => 'required|in:punch_in,punch_out',
            'punch_time' => 'required|date_format:Y-m-d H:i:s', // 確保格式正確
            'reason' => 'required|string',
        ]);

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

        // 設定預設的 review_message
        $reviewMessage = $validatedData['review_message'] ?? '審核通過';

        // 更新補登申請的狀態
        $correction->update([
            'status' => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'review_message' => $reviewMessage, // 儲存管理員的說明
        ]);

        return response()->json([
            'message' => '補登已通過審核',
            'data' => $correction
        ], 200);
    }


    // 3️⃣ 管理員審核（拒絕）
    public function reject(Request $request, $id)
    {
        // $request->validate([
        //     'review_message' => 'required|string|max:255' // 必須填寫拒絕原因
        // ]);

        if (!$request->filled('review_message')) {
            return response()->json([
                'message' => '請填寫拒絕原因'
            ], 400);
        }

        $correction = PunchCorrection::findOrFail($id);

        // 確保這筆補登還未審核
        if ($correction->status !== 'pending') {
            return response()->json(['message' => '此補登請求已審核，無法修改'], 400);
        }

        $correction->update([
            'status' => 'rejected',
            'review_message' => $request->review_message, // 儲存管理員的說明
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        return response()->json([
            'message' => '補登請求已被拒絕',
            'data' => $correction
        ], 200);
    }

    // 使用者可以查看自己的所有打卡補登紀錄(可選擇日期範圍)
    public function getUserCorrections(Request $request)
    {
        // 確保使用者已登入
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => '未授權的請求'], 401);
        }

        // 取得 Query 參數
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        // 查詢使用者的補登紀錄
        $query = PunchCorrection::where('user_id', $user->id);

        // 若有提供開始 & 結束日期，則篩選日期範圍
        if ($startDate && $endDate) {
            $query->whereBetween('punch_time', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        }

        // 取得結果，依時間排序（最新的在最上面）
        $corrections = $query->orderBy('punch_time', 'desc')->get();

        return response()->json([
            'message' => '成功獲取補登紀錄',
            'data' => $corrections
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

        // 呼叫 MySQL 預存程序
        $records = DB::select('CALL GetFinalAttendanceRecords(?,?,?)', [
            $userId,
            $startDate,
            $endDate,
        ]);

        return response()->json($records);
    }

    public function getAllCorrections(Request $request)
    {
        // 1️⃣ 確保使用者已登入
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => '未授權的請求'], 401);
        }

        // 2️⃣ 確保使用者有 HR 或管理員權限
        if (!$user->hasRole(['HR', 'admin'])) {
            return response()->json(['message' => '您沒有權限查看所有補登紀錄'], 403);
        }

        // 3️⃣ 取得 Query 參數
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $userId = $request->query('user_id');

        // 4️⃣ 建立查詢
        $query = PunchCorrection::with('user'); // 讓結果包含使用者資訊

        // 5️⃣ 如果有提供 `user_id`，則篩選特定使用者
        if ($userId) {
            $query->where('user_id', $userId);
        }

        // 6️⃣ 如果有提供 `start_date` 和 `end_date`，則篩選日期範圍
        if ($startDate && $endDate) {
            $query->whereBetween('punch_time', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        }

        // 7️⃣ 取得結果，依時間排序（最新的在最上面）
        $corrections = $query->orderBy('punch_time', 'desc')->get();

        return response()->json([
            'message' => '成功獲取所有補登紀錄',
            'data' => $corrections
        ], 200);
    }
}

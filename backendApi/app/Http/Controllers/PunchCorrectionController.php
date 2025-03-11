<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PunchCorrection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

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

        // 先按照 `status = 'pending'` 排序，然後再按照 `punch_time` 由新到舊排序
        $corrections = $query
            ->orderByRaw("CASE WHEN status = 'pending' THEN 1 ELSE 2 END") // 讓 'pending' 的資料排在最前面
            ->orderBy('punch_time', 'desc') // 再按照 `punch_time` 由新到舊排序
            ->get();

        return response()->json([
            'message' => '成功獲取補登紀錄',
            'data' => $corrections
        ], 200);
    }

    // 個人的打卡紀錄
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

    // 讓人資看到所有人的打卡紀錄
    public function getAllFinalAttendanceRecords(Request $request)
    {
        // 確保使用者已登入
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => '未授權的請求'], 401);
        }

        // 取得 Query 參數
        $departmentId = $request->query('department_id');
        $year = $request->query('year');
        $month = $request->query('month');
        $page = $request->query('page', 1); // 預設第一頁
        $perPage = (int) $request->query('per_page', 10); //每頁顯示10個user_id

        // 驗證 year & month
        if (!$year || !$month) {
            return response()->json(['error' => '請提供年份和月份'], 400);
        }

        // 避免 page 或 perPage 為負數
        $page = max(1, $page);
        $perPage = max(1, 10);

        // ✅ 第一次查詢：計算總 user 數量
        $totalUsersResult = DB::select("
            SELECT COUNT(DISTINCT user_id) AS total_users
            FROM (
                SELECT user_id FROM punch_corrections 
                WHERE status = 'approved' 
                AND YEAR(punch_time) = ? AND MONTH(punch_time) = ?
                
                UNION

                SELECT user_id FROM punch_ins 
                WHERE YEAR(timestamp) = ? AND MONTH(timestamp) = ?

                UNION

                SELECT user_id FROM punch_outs 
                WHERE YEAR(timestamp) = ? AND MONTH(timestamp) = ?
            ) AS all_users
            WHERE user_id IN (
                SELECT id FROM employees WHERE status != 'inactive'
                AND (? IS NULL OR department_id = ?) -- 如果 departmentId 為 NULL 則不過濾
            )
        ", [$year, $month, $year, $month, $year, $month, $departmentId, $departmentId]);

        // **獲取 `total_users`**
        $totalUsers = $totalUsersResult[0]->total_users ?? 0;

        // 呼叫 MySQL 預存程序
        $records = DB::select('CALL GetAllFinalAttendanceRecords(?, ?, ?, ?, ?)', [
            $departmentId ?: null,
            $year,
            $month,
            $page,
            $perPage
        ]);

        // **整理回傳格式**
        $groupedData = [];
        foreach ($records as $record) {
            $userId = $record->user_id;
            $userName = $record->user_name;

            if (!isset($groupedData[$userId])) {
                $groupedData[$userId] = [
                    'user_id' => $userId,
                    'user_name' => $userName,
                    'records' => []
                ];
            }

            // 限制每個使用者最多 31 筆資料
            if (count($groupedData[$userId]['records']) < 31) {
                $groupedData[$userId]['records'][] = [
                    'date' => $record->date,
                    'punch_in' => $record->punch_in,
                    'punch_out' => $record->punch_out
                ];
            }
        }

        // **計算分頁資訊**
        $lastPage = max(1, ceil($totalUsers / $perPage));
        $nextPageUrl = $page < $lastPage ? url("/api/attendance/records?page=" . ($page + 1) . "&per_page=" . $perPage) : null;
        $prevPageUrl = $page > 1 ? url("/api/attendance/records?page=" . ($page - 1) . "&per_page=" . $perPage) : null;

        // **統一 API 分頁格式**
        return response()->json([
            'message' => '成功獲取所有員工的打卡紀錄',
            'data' => [
                'data' => array_values($groupedData), // 確保輸出為數組
                'current_page' => $page, // 當前頁碼
                'per_page' => $perPage, // 每頁顯示筆數
                'total' => $totalUsers, // 總筆數(所有資料的總數)
                'last_page' => $lastPage, // 總頁數
                'from' => ($page - 1) * $perPage + 1, // 當前頁的第一筆資料的索引
                'to' => min($page * $perPage, $totalUsers), // 當前頁的最後一筆資料的索引
                'first_page_url' => url("/api/attendance/records?page=1&per_page=" . $perPage), // 第一頁的 API URL
                'last_page_url' => url("/api/attendance/records?page=" . $lastPage . "&per_page=" . $perPage), // 最後一頁的 API URL
                'next_page_url' => $nextPageUrl, // 最後一頁的 API URL
                'prev_page_url' => $prevPageUrl, // 上一頁的 API URL（如果有）
                'path' => url("/api/attendance/records") // API 路徑（不帶分頁參數）
            ]
        ], 200);
    }

    // 人資查看所有補登打卡申請
    public function getAllCorrections(Request $request)
    {
        // 確保使用者已登入
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => '未授權的請求'], 401);
        }

        // 取得 Query 參數
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $page = $request->query('page', 1); // 預設第一頁
        $perPage = (int) $request->query('per_page', 10); //每頁顯示10個user_id

        // 避免 page 或 perPage 為負數
        $page = max(1, $page);
        $perPage = max(1, min($perPage, 10));

        // **✅ 第一次查詢：計算符合條件的 `totalRecords`**
        $totalRecordsResult = DB::select("
            SELECT COUNT(*) AS total_records
            FROM punch_corrections pc
            WHERE (pc.punch_time >= ? OR ? IS NULL)
            AND (pc.punch_time <= ? + INTERVAL 1 DAY OR ? IS NULL)
            AND pc.user_id IN (SELECT id FROM employees WHERE status != 'inactive')
        ", [$startDate, $startDate, $endDate, $endDate]);

        // **獲取 `total_records`**
        $totalRecords = $totalRecordsResult[0]->total_records ?? 0;

        // 呼叫 MySQL 預存程序
        $corrections = DB::select('CALL GetAllPunchCorrections(?, ?, ?, ?)', [
            $startDate ?: null,   // 如果沒傳 start_date，則傳 NULL
            $endDate ?: null,      // 如果沒傳 end_date，則傳 NULL
            $page,
            $perPage
        ]);

        // **計算分頁資訊**
        $lastPage = max(1, ceil($totalRecords / $perPage));
        $nextPageUrl = $page < $lastPage ? url("/api/corrections?page=" . ($page + 1) . "&per_page=" . $perPage) : null;
        $prevPageUrl = $page > 1 ? url("/api/corrections?page=" . ($page - 1) . "&per_page=" . $perPage) : null;

        // **統一 API 分頁格式**
        return response()->json([
            'message' => '成功獲取所有補登紀錄',
            'data' => [
                'data' => $corrections,  // 直接返回補登打卡資料
                'current_page' => $page, // 目前頁碼
                'per_page' => $perPage, // 每頁顯示筆數
                'total' => $totalRecords, // 總筆數（所有資料的總數）
                'last_page' => $lastPage, // 總頁數
                'from' => ($page - 1) * $perPage + 1,
                'to' => min($page * $perPage, $totalRecords),
                'first_page_url' => url("/api/corrections?page=1&per_page=" . $perPage),
                'last_page_url' => url("/api/corrections?page=" . $lastPage . "&per_page=" . $perPage),
                'next_page_url' => $nextPageUrl,
                'prev_page_url' => $prevPageUrl,
                'path' => url("/api/corrections")
            ]
        ], 200);
    }
}

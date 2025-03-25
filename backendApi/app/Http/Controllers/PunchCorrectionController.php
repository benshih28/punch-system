<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PunchCorrection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class PunchCorrectionController extends Controller
{
    // 強制回傳 JSON
    public function __construct()
    {
        request()->headers->set('Accept', 'application/json');
    }


    // 提交補登請求
    /**
     * @OA\Post(
     *     path="/api/punch/correction",
     *     summary="提交補登請求",
     *     description="使用者提交補登請求",
     *     operationId="requestPunchCorrection",
     *     tags={"Punch Correction"},
     *     security={{ "bearerAuth":{} }},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"correction_type", "punch_time", "reason"},
     *             @OA\Property(property="correction_type", type="string", enum={"punch_in", "punch_out"}, example="punch_in"),
     *             @OA\Property(property="punch_time", type="string", format="date-time", example="2025-03-11 08:00:00"),
     *             @OA\Property(property="reason", type="string", example="忘記打卡")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="補登申請成功",
     *         @OA\JsonContent(ref="#/components/schemas/PunchCorrection")
     *     ),
     *     @OA\Response(response=400, description="打卡時間不能是未來時間"),
     *     @OA\Response(response=401, description="未授權")
     * )
     */
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

        // 刪除補登申請
    /**
     * @OA\Delete(
     *     path="/api/punch/correction/{id}",
     *     summary="刪除補登申請",
     *     description="使用者或管理員刪除補登申請",
     *     operationId="deletePunchCorrection",
     *     tags={"Punch Correction"},
     *     security={{ "bearerAuth":{} }},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="補登請求 ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="補登申請已刪除",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="補登申請已刪除")
     *         )
     *     ),
     *     @OA\Response(response=401, description="未授權"),
     *     @OA\Response(response=404, description="找不到補登申請")
     * )
     */
    public function destroy($id)
    {
        // 確保使用者已登入
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => '未授權的請求'], 401);
        }

        // 找到補登申請
        $correction = PunchCorrection::findOrFail($id);

        // 刪除補登申請
        $correction->delete();

        return response()->json(['message' => '補登申請已刪除'], 200);
    }

        // 修改補登申請
    /**
     * @OA\Put(
     *     path="/api/punch/correction/{id}",
     *     summary="修改補登申請",
     *     description="使用者修改補登申請",
     *     operationId="updatePunchCorrection",
     *     tags={"Punch Correction"},
     *     security={{ "bearerAuth":{} }},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="補登請求 ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"correction_type", "punch_time", "reason"},
     *             @OA\Property(property="correction_type", type="string", enum={"punch_in", "punch_out"}, example="punch_in"),
     *             @OA\Property(property="punch_time", type="string", format="date-time", example="2025-03-11 08:00:00"),
     *             @OA\Property(property="reason", type="string", example="忘記打卡")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="補登申請已修改",
     *         @OA\JsonContent(ref="#/components/schemas/PunchCorrection")
     *     ),
     *     @OA\Response(response=400, description="打卡時間不能是未來時間"),
     *     @OA\Response(response=401, description="未授權"),
     *     @OA\Response(response=404, description="找不到補登申請")
     * )
     */
    public function update(Request $request, $id)
    {
        // 確保使用者已登入
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => '未授權的請求'], 401);
        }

        // 找到補登申請
        $correction = PunchCorrection::findOrFail($id);

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

        // 更新補登申請
        $correction->update([
            'correction_type' => $validatedData['correction_type'],
            'punch_time' => $punchTime,
            'reason' => $validatedData['reason'],
        ]);

        return response()->json([
            'message' => '補登申請已修改',
            'data' => $correction
        ], 200);
    }

    // 管理員審核（批准）
    /**
     * @OA\Put(
     *     path="/api/punch/correction/{id}/approve",
     *     summary="審核補登申請（通過）",
     *     description="管理員審核通過補登申請",
     *     operationId="approvePunchCorrection",
     *     tags={"Punch Correction"},
     *     security={{ "bearerAuth":{} }},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="補登請求 ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="review_message", type="string", example="審核通過")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="補登已通過審核",
     *         @OA\JsonContent(ref="#/components/schemas/PunchCorrection")
     *     ),
     *     @OA\Response(response=400, description="此補登申請已被處理"),
     *     @OA\Response(response=401, description="未授權")
     * )
     */
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


    // 管理員審核（拒絕）
    /**
     * @OA\Put(
     *     path="/api/punch/correction/{id}/reject",
     *     summary="審核補登申請（拒絕）",
     *     description="管理員拒絕補登申請",
     *     operationId="rejectPunchCorrection",
     *     tags={"Punch Correction"},
     *     security={{ "bearerAuth":{} }},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="補登請求 ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"review_message"},
     *             @OA\Property(property="review_message", type="string", example="補登原因不合理")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="補登請求已被拒絕",
     *         @OA\JsonContent(ref="#/components/schemas/PunchCorrection")
     *     ),
     *     @OA\Response(response=400, description="請填寫拒絕原因"),
     *     @OA\Response(response=401, description="未授權")
     * )
     */
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
    /**
     * @OA\Get(
     *     path="/api/punch/correction",
     *     summary="取得個人補登紀錄",
     *     description="使用者可查詢自己的補登紀錄（可篩選日期）",
     *     operationId="getUserPunchCorrections",
     *     tags={"Punch Correction"},
     *     security={{ "bearerAuth":{} }},
     *     @OA\Parameter(name="start_date", in="query", description="開始日期", @OA\Schema(type="string", format="date", example="2025-03-01")),
     *     @OA\Parameter(name="end_date", in="query", description="結束日期", @OA\Schema(type="string", format="date", example="2025-03-10")),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="分頁頁碼（預設 1）",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="每頁顯示數量（預設 10）",
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="成功獲取補登紀錄",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/PunchCorrection"))
     *     ),
     *     @OA\Response(response=401, description="未授權")
     * )
     */
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
        $page = (int) $request->query('page', 1);
        $perPage = (int) $request->query('per_page', 10);

        // 確保 page 和 perPage 為正數
        $page = max(1, $page);
        $perPage = max(1, $perPage);

        // **呼叫 MySQL 預存程序**
        $records = DB::select('CALL GetUserPunchCorrections(?, ?, ?, ?, ?)', [
            $user->id,
            $startDate ?: null,
            $endDate ?: null,
            $page,
            $perPage
        ]);

        // **取得總筆數 (從預存程序的第一筆資料)**
        $totalRecords = count($records) > 0 ? $records[0]->total_records : 0;

        // **計算分頁資訊**
        $lastPage = max(1, ceil($totalRecords / $perPage));
        $nextPageUrl = $page < $lastPage ? url("/api/punch/corrections?page=" . ($page + 1) . "&per_page=" . $perPage) : null;
        $prevPageUrl = $page > 1 ? url("/api/punch/corrections?page=" . ($page - 1) . "&per_page=" . $perPage) : null;

        // **統一 API 分頁格式**
        return response()->json([
            'message' => '成功獲取補登紀錄',
            'data' => [
                'data' => $records,
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $totalRecords,
                'last_page' => $lastPage,
                'from' => ($page - 1) * $perPage + 1,
                'to' => min($page * $perPage, $totalRecords),
                'first_page_url' => url("/api/punch/corrections?page=1&per_page=" . $perPage),
                'last_page_url' => url("/api/punch/corrections?page=" . $lastPage . "&per_page=" . $perPage),
                'next_page_url' => $nextPageUrl,
                'prev_page_url' => $prevPageUrl,
                'path' => url("/api/punch/corrections")
            ]
        ], 200);
    }


    // 讓人資看到所有人的打卡紀錄
    /**
     * @OA\Get(
     *     path="/api/attendancerecords",
     *     summary="查詢所有員工的打卡紀錄",
     *     description="人資或主管可查詢所有員工的打卡紀錄（可依部門、使用者ID、年份、月份篩選）",
     *     operationId="getAllAttendanceRecords",
     *     tags={"Attendance"},
     *     security={{ "bearerAuth":{} }},
     *     @OA\Parameter(
     *         name="department_id",
     *         in="query",
     *         description="部門 ID（選填，人資可指定要查詢的部門）",
     *         @OA\Schema(type="integer", example=2)
     *     ),
     *     @OA\Parameter(
     *         name="user_id",
     *         in="query",
     *         description="特定使用者 ID（選填）",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="year",
     *         in="query",
     *         required=true,
     *         description="查詢年份",
     *         @OA\Schema(type="integer", example=2025)
     *     ),
     *     @OA\Parameter(
     *         name="month",
     *         in="query",
     *         required=true,
     *         description="查詢月份",
     *         @OA\Schema(type="integer", example=3)
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="分頁頁碼（預設 1）",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="每頁顯示數量（預設 10）",
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="成功獲取所有員工的打卡紀錄",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="成功獲取所有員工的打卡紀錄"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/AttendanceRecord")),
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="per_page", type="integer", example=10),
     *                 @OA\Property(property="total", type="integer", example=50),
     *                 @OA\Property(property="last_page", type="integer", example=5),
     *                 @OA\Property(property="from", type="integer", example=1),
     *                 @OA\Property(property="to", type="integer", example=10),
     *                 @OA\Property(property="first_page_url", type="string", example="http://127.0.0.1:8000/api/attendancerecords?page=1&per_page=10"),
     *                 @OA\Property(property="last_page_url", type="string", example="http://127.0.0.1:8000/api/attendancerecords?page=5&per_page=10"),
     *                 @OA\Property(property="next_page_url", type="string", nullable=true, example="http://127.0.0.1:8000/api/attendancerecords?page=2&per_page=10"),
     *                 @OA\Property(property="prev_page_url", type="string", nullable=true, example=null),
     *                 @OA\Property(property="path", type="string", example="http://127.0.0.1:8000/api/attendancerecords")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=400, description="請提供年份和月份"),
     *     @OA\Response(response=401, description="未授權"),
     *     @OA\Response(response=404, description="找不到符合條件的打卡紀錄")
     * )
     * 
     * @OA\Get(
     *     path="/api/attendance/record",
     *     summary="查詢個人打卡紀錄",
     *     description="讓使用者查詢自己的打卡紀錄，需具有 `view_attendance` 權限。",
     *     operationId="getPersonalAttendanceRecords",
     *     tags={"Attendance"},
     *     security={{ "bearerAuth":{} }},
     *     @OA\Parameter(
     *         name="year",
     *         in="query",
     *         required=true,
     *         description="查詢年份",
     *         @OA\Schema(type="integer", example=2025)
     *     ),
     *     @OA\Parameter(
     *         name="month",
     *         in="query",
     *         required=true,
     *         description="查詢月份",
     *         @OA\Schema(type="integer", example=3)
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="分頁頁碼（預設 1）",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="每頁顯示數量（預設 10）",
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="成功獲取個人打卡紀錄",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="成功獲取個人打卡紀錄"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/AttendanceRecord")),
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="per_page", type="integer", example=10),
     *                 @OA\Property(property="total", type="integer", example=10),
     *                 @OA\Property(property="last_page", type="integer", example=1),
     *                 @OA\Property(property="from", type="integer", example=1),
     *                 @OA\Property(property="to", type="integer", example=10)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=400, description="請提供年份和月份"),
     *     @OA\Response(response=401, description="未授權")
     * )
     */

    public function getAllAttendanceRecords(Request $request)
    {
        // 確保使用者已登入
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => '未授權的請求'], 401);
        }
        $employee = $user->employee; // 取得對應的 employees 資料

        // ✅ 取得使用者角色和 ID
        $requesterId = $user->id;
        $departmentId = optional($employee)->department_id; // 取得部門 ID
        $managerId = optional($employee)->manager_id; // 取得 manager_id

        // 取得 Query 參數
        $requestedDepartmentId = $request->query('department_id'); // 讓 HR 可以指定要查詢的部門
        $userId = $request->query('user_id'); // 指定查詢的 user ID (可選)
        $year = $request->query('year');
        $month = $request->query('month');
        $page = (int) $request->query('page', 1); // 預設第一頁
        $perPage = (int) $request->query('per_page', 10); //每頁顯示10個user_id

        // 驗證 year & month
        if (!$year || !$month) {
            return response()->json(['error' => '請提供年份和月份'], 400);
        }

        // 避免 page 或 perPage 為負數
        $page = max(1, $page);
        $perPage = max(1, $perPage);

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
                SELECT e.id FROM employees e
                WHERE e.status != 'inactive'
                AND ( ? IS NULL OR e.department_id = ? ) -- ✅ 直接比對 `department_id`
            )
        ", [
            $year,
            $month, // punch_corrections
            $year,
            $month, // punch_ins
            $year,
            $month, // punch_outs
            $departmentId,  // ✅ 直接用 `departmentId` 來過濾
            $departmentId   // ✅ 避免 COLLATE 轉換，提升效能
        ]);

        // **獲取 `total_users`**
        $totalUsers = count($totalUsersResult) > 0 ? $totalUsersResult[0]->total_users : 0; // 計算總使用者數量

        // **允許 HR 指定 department_id 進行查詢**
        if ($departmentId == 1) {
            $finalDepartmentId = $requestedDepartmentId ?? null; // HR 可以查詢所有部門 (NULL = 不限部門)
        } else {
            $finalDepartmentId = $departmentId; // 主管 & 員工只能查詢自己的部門
        }

        // ✅ 呼叫 MySQL 預存程序，取得該分頁的資料
        $records = DB::select('CALL GetAllFinalAttendanceRecords(?, ?, ?, ?, ?, ?, ?, ?, ?)', [
            $requesterId,     // 當前登入者的 user_id
            $departmentId,    // 當前登入者的部門 ID
            $managerId,       // 當前登入者的 manager_id
            $finalDepartmentId,
            $userId ?? null,  // 選擇性查詢特定 user_id
            $year,            // 查詢年份
            $month,           // 查詢月份
            $page,            // 當前頁數
            $perPage          // 每頁筆數
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
        $nextPageUrl = $page < $lastPage ? url("/api/attendancerecords?page=" . ($page + 1) . "&per_page=" . $perPage) : null;
        $prevPageUrl = $page > 1 ? url("/api/attendancerecords?page=" . ($page - 1) . "&per_page=" . $perPage) : null;

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
                'first_page_url' => url("/api/attendancerecords?page=1&per_page=" . $perPage), // 第一頁的 API URL
                'last_page_url' => url("/api/attendancerecords?page=" . $lastPage . "&per_page=" . $perPage), // 最後一頁的 API URL
                'next_page_url' => $nextPageUrl, // 最後一頁的 API URL
                'prev_page_url' => $prevPageUrl, // 上一頁的 API URL（如果有）
                'path' => url("/api/attendancerecords") // API 路徑（不帶分頁參數）
            ]
        ], 200);
    }

    // 人資查看所有補登打卡申請
    /**
     * @OA\Get(
     *     path="/api/corrections",
     *     summary="查看所有補登申請",
     *     description="人資可查看所有補登申請（可篩選日期）",
     *     operationId="getAllPunchCorrections",
     *     tags={"Punch Correction"},
     *     security={{ "bearerAuth":{} }},
     *     
     *     @OA\Parameter(
     *         name="department_id",
     *         in="query",
     *         description="部門 ID（選填）",
     *         required=false,
     *         @OA\Schema(type="integer", example=2)
     *     ),
     *     @OA\Parameter(
     *         name="user_id",
     *         in="query",
     *         description="特定員工 ID（選填）",
     *         required=false,
     *         @OA\Schema(type="integer", example=5)
     *     ),
     *     @OA\Parameter(
     *         name="start_date",
     *         in="query",
     *         description="開始日期",
     *         required=false,
     *         @OA\Schema(type="string", format="date", example="2025-03-01")
     *     ),
     *     @OA\Parameter(
     *         name="end_date",
     *         in="query",
     *         description="結束日期",
     *         required=false,
     *         @OA\Schema(type="string", format="date", example="2025-03-10")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="分頁頁碼（預設 1）",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     
     *     @OA\Response(
     *         response=200,
     *         description="成功獲取補登紀錄",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="total_records", type="integer", example=11),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/PunchCorrection")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="未授權")
     * )
     */

    public function getAllCorrections(Request $request)
    {
        // 確保使用者已登入
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => '未授權的請求'], 401);
        }

        // 取得 Query 參數
        $departmentId = $request->query('department_id');
        $userId = $request->query('user_id');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $page = $request->query('page', 1); // 預設第一頁
        $perPage = (int) $request->query('per_page', 10); //每頁顯示10個user_id

        // 避免 page 或 perPage 為負數
        $page = max(1, $page);
        $perPage = max(1, $perPage);

        // 呼叫 MySQL 預存程序
        $corrections = DB::select('CALL GetAllPunchCorrections(?, ?, ?, ?, ?, ?)', [
            $departmentId ?: null,
            $userId ?: null,
            $startDate ?: null,   // 如果沒傳 start_date，則傳 NULL
            $endDate ?: null,      // 如果沒傳 end_date，則傳 NULL
            $page,
            $perPage
        ]);

        // 取得總筆數
        $totalRecords = count($corrections) > 0 ? $corrections[0]->total_records : 0;

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

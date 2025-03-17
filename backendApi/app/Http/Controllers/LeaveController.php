<?php

namespace App\Http\Controllers;

use App\Http\Requests\LeaveApplyRequest; // Ensure this class exists in the specified namespace
use App\Http\Requests\LeaveListRequest;
use App\Http\Requests\LeaveUpdateRequest;
use App\Models\Employee; // 確保引入 Employee 模型
use App\Models\LeaveType; // 確保引入 LeaveType 模型
use App\Services\LeaveService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\Leave;
use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Str;


class LeaveController extends Controller
{
    protected $leaveService;

    public function __construct(LeaveService $leaveService)
    {
        $this->leaveService = $leaveService;
    }

    // 1. 員工申請請假
    /**
     * @OA\Post(
     *     path="/api/leave/request",
     *     summary="請假申請",
     *     description="員工請假申請。",
     *     operationId="requestLeave",
     *     tags={"Leave"},
     *     security={{ "bearerAuth": {} }},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 type="object",
     *                 required={"start_time", "end_time", "leave_type_id", "reason"},
     *                 @OA\Property(property="start_time", type="string", format="date-time", example="2025-03-14 09:00"),
     *                 @OA\Property(property="end_time", type="string", format="date-time", example="2025-03-14 18:00"),
     *                 @OA\Property(property="leave_type_id", type="integer", example=1),
     *                 @OA\Property(property="reason", type="string", example="需要休息"),
     *                 @OA\Property(property="attachment", type="file", format="binary", nullable=true, description="選擇要上傳的附件檔案")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="成功遞出假單",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="申請成功，假單已送出"),
     *             @OA\Property(property="leave", type="object", 
     *                 @OA\Property(property="id", type="integer", example=123),
     *                 @OA\Property(property="start_time", type="string", format="date-time", example="2025-03-14T09:00"),
     *                 @OA\Property(property="end_time", type="string", format="date-time", example="2025-03-14T17:00"),
     *                 @OA\Property(property="leave_type", type="string", example="Sick Leave"),
     *                 @OA\Property(property="status", type="integer", example=0),
     *                 @OA\Property(property="attachment", type="file", example="attachment/yourfilesname.jpg")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="無法申請生理假，使用者非女性",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="您無法申請生理假")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="伺服器錯誤，請檢查資料是否正確",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="申請失敗，請檢查填入資料是否有誤"),
     *             @OA\Property(property="error", type="string", nullable=true, example="資料格式錯誤")
     *         )
     *     )
     * )
     */
    public function requestLeave(LeaveApplyRequest $request): JsonResponse
    {
        try {
            // 1️⃣ 透過 JWT 取得當前登入者
            $user = auth()->user();
            $leaveType = LeaveType::find($request->input('leave_type_id'));

            // 2️⃣ **資料驗證**
            $data = $request->validated();
            $data['user_id'] = $user->id; // 由後端自動填入 `user_id`
            $data['status'] = 0;
            $data['attachment'] = null; // **預設 `attachment` 為 `null`，避免未定義錯誤**

            // **如果是假別是生理假，但使用者不是女性，則拒絕請假**
            if ($leaveType->name === 'Menstrual Leave' && $user->gender !== 'female') {
                return response()->json(['message' => '您無法申請生理假'], 403);
            }

            // 3️⃣ **處理附件**（如果沒有附件，`attachment` 保持 `null`）
            $fileRecord = null;
            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');

                // 產生唯一檔名
                $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
                $attachmentPath = $file->storeAs('attachments', $filename, 'public');

                // **存入 `files` 表**
                $fileRecord = File::create([
                    'user_id' => $user->id,
                    'leave_id' => null, // 先不關聯 `leave_id`，稍後再更新
                    'leave_attachment' => str_replace('public/', '', $attachmentPath), // ✅ 存成相對路徑
                ]);

                // **將附件 ID 存入 `$data`，傳遞給 Service**
                $data['attachment'] = $fileRecord->id;
            }

            // 4️⃣ **呼叫 Service 層處理請假**
            $leave = $this->leaveService->applyLeave($data);

            // 5️⃣ **如果有附件，更新 `leave_id` 到 `File` 表**
            if ($fileRecord) {
                $fileRecord->update(['leave_id' => $leave->id]);
            }

            // 6️⃣ **回傳成功資訊**
            return response()->json([
                'message' => '申請成功，假單已送出',
                'leave' => $this->formatLeave($leave),
            ], 201); // **201 Created：表示成功建立新資源**

        } catch (\Throwable $e) {
            // 7️⃣ **回傳錯誤資訊**
            return response()->json([
                'message' => '申請失敗，請檢查填入資料是否有誤',
                'error' => app()->isLocal() ? $e->getMessage() : null, // **本機開發環境才回傳錯誤**
            ], 500);
        }
    }

    // 2. 查詢個人請假紀錄 (員工)
    /**
     * @OA\Get(
     *     path="/api/leave/my-records",
     *     summary="查詢個人請假紀錄",
     *     description="查詢符合條件的請假紀錄。",
     *     operationId="viewMyLeaveRecords",
     *     tags={"Leave"},
     *     security={{"bearerAuth":{}}},
     * 
     *     @OA\Parameter(
     *         name="start_date",
     *         in="query",
     *         description="查詢開始日期 (格式: YYYY-MM-DD)",
     *         required=true,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="end_date",
     *         in="query",
     *         description="查詢結束日期 (格式: YYYY-MM-DD)，需大於等於 start_date",
     *         required=true,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="leave_type",
     *         in="query",
     *         description="請假類型 (leave_types 表中的 ID)",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="attachment",
     *         in="query",
     *         description="附件 ID (files 表中的 ID)，可選",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="請假狀態 (0: 待審核, 1: 主管通過, 2: 主管拒絕, 3: HR同意, 4: HR拒絕)",
     *         required=false,
     *         @OA\Schema(type="integer", enum={0,1,2,3,4})
     *     ),
     * 
     *     @OA\Response(
     *         response=200,
     *         description="請假紀錄查詢成功",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="查詢成功"),
     *             @OA\Property(property="records", type="array",
     *                 @OA\Items(type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="start_date", type="string", format="date", example="2024-03-01"),
     *                     @OA\Property(property="end_date", type="string", format="date", example="2024-03-03"),
     *                     @OA\Property(property="leave_type", type="string", example="病假"),
     *                     @OA\Property(property="status", type="integer", example=2),
     *                     @OA\Property(property="attachment", type="string", example="file_id_123"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2024-03-01T08:00:00Z")
     *                 )
     *             )
     *         )
     *     ),
     * 
     *     @OA\Response(
     *         response=403,
     *         description="無權限查詢請假紀錄",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="無此權限，無法查詢請假紀錄")
     *         )
     *     ),
     * 
     *     @OA\Response(
     *         response=500,
     *         description="查詢失敗 (系統錯誤)",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="查詢失敗")
     *         )
     *     )
     * )
     */
    public function viewMyLeaveRecords(LeaveListRequest $request): JsonResponse
    {
        $user = auth()->user();
        if ($user->employee->status === 'inactive') {
            return response()->json(['message' => '無此權限，無法查詢請假紀錄'], 403);
        }

        try {
            $filters = $request->validated();

            // Log::info('查詢請假紀錄', ['user_id' => $user->id, 'filters' => $filters]);

            $leaves = $this->leaveService->getLeaveList($user, $filters);

            if ($leaves->isEmpty()) {
                return response()->json([
                    'message' => '查無符合條件的資料',
                    'records' => [],
                ], 200);
            }

            return response()->json([
                'message' => '查詢成功',
                'records' => $leaves->map(fn($leave) => $this->formatLeave($leave)),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => app()->isLocal() ? $e->getMessage() : '查詢失敗',
            ], 500);
        }
    }

    // 3. 查詢「部門」請假紀錄（主管 & HR）
    /**
     * @OA\Get(
     *     path="/api/leave/department",
     *     summary="查詢部門請假紀錄",
     *     description="讓部門主管或 HR 查詢部門內所有請假紀錄，支援條件篩選。",
     *     operationId="viewDepartmentLeaveRecords",
     *     tags={"Leave"},
     *     security={{"bearerAuth":{}}},
     * 
     *     @OA\Parameter(
     *         name="leave_type_id",
     *         in="query",
     *         description="請假類型 ID (leave_types 表中的 ID)",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="start_date",
     *         in="query",
     *         description="查詢開始日期 (格式: YYYY-MM-DD)",
     *         required=true,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="end_date",
     *         in="query",
     *         description="查詢結束日期 (格式: YYYY-MM-DD)，需大於等於 start_date",
     *         required=true,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="請假狀態 (0: 待審核, 1: 主管通過, 2: 主管拒絕, 3: HR同意, 4: HR拒絕)",
     *         required=false,
     *         @OA\Schema(type="integer", enum={0,1,2,3,4})
     *     ),
     * 
     *     @OA\Response(
     *         response=200,
     *         description="查詢成功",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="查詢成功"),
     *             @OA\Property(property="records", type="array",
     *                 @OA\Items(type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="employee_name", type="string", example="王小明"),
     *                     @OA\Property(property="department", type="string", example="技術部"),
     *                     @OA\Property(property="start_date", type="string", format="date", example="2024-03-01"),
     *                     @OA\Property(property="end_date", type="string", format="date", example="2024-03-03"),
     *                     @OA\Property(property="leave_type", type="string", example="病假"),
     *                     @OA\Property(property="status", type="integer", example=2),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2024-03-01T08:00:00Z")
     *                 )
     *             ),
     *             @OA\Property(property="total", type="integer", example=5, description="符合條件的請假紀錄總數")
     *         )
     *     ),
     * 
     *     @OA\Response(
     *         response=403,
     *         description="無權限查詢請假紀錄",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="無此權限，無法查詢請假紀錄")
     *         )
     *     ),
     * 
     *     @OA\Response(
     *         response=500,
     *         description="伺服器錯誤",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="查詢失敗")
     *         )
     *     )
     * )
     */
    public function viewDepartmentLeaveRecords(Request $request): JsonResponse
    {
        $user = auth()->user();

        if ($user->employee->status === 'inactive') {
            return response()->json(['message' => '無此權限，無法查詢請假紀錄'], 403);
        }

        try {
            $filters = $request->validate([
                'leave_type_id' => 'nullable|exists:leave_types,id',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'status' => 'nullable|integer|in:0,1,2,3,4',
            ]);

            // Log::info('查詢部門請假紀錄', ['user_id' => $user->id, 'filters' => $filters]);

            $leaves = $this->leaveService->getDepartmentLeaveList($user, $filters);

            if ($leaves->total() === 0) {            //這裡開始
                return response()->json([
                    'message' => '查無符合條件的請假紀錄',
                    'records' => [],
                    'total' => 0,
                ], 200);
            }

            return response()->json([
                'message' => '查詢成功',
                'records' => $leaves->map(fn($leave) => $this->formatLeave($leave)),
                'total' => $leaves->total(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => app()->isLocal() ? $e->getMessage() : '查詢失敗',
            ], 500);
        }
    }

    // 4. 查詢「全公司」請假紀錄 (HR)
    /**
     * @OA\Get(
     *     path="/api/leave/company",
     *     summary="查詢全公司請假紀錄",
     *     description="HR可查詢全公司員工的請假紀錄",
     *     tags={"Leave"},
     *     security={{ "bearerAuth": {} }},
     *     @OA\Parameter(
     *         name="leave_type_id",
     *         in="query",
     *         description="請假類型ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="start_date",
     *         in="query",
     *         description="起始日期",
     *         required=true,
     *         @OA\Schema(type="string", format="date", example="2025-03-01")
     *     ),
     *     @OA\Parameter(
     *         name="end_date",
     *         in="query",
     *         description="結束日期",
     *         required=true,
     *         @OA\Schema(type="string", format="date", example="2025-03-31")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="請假狀態 (0: 待審核, 1: 主管通過, 2: 主管拒絕, 3: HR同意, 4: HR拒絕)",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="查詢成功",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="查詢成功"),
     *             @OA\Property(property="records", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=456),
     *                 @OA\Property(property="leave_type", type="string", example="Annual Leave"),
     *                 @OA\Property(property="start_date", type="string", format="date", example="2025-03-15 09:00"),
     *                 @OA\Property(property="end_date", type="string", format="date", example="2025-03-17 18:00"),
     *                 @OA\Property(property="status", type="integer", example=1, description="0: 待審核, 1: 主管通過, 2: 主管拒絕, 3: HR同意, 4: HR拒絕"),
     *                 @OA\Property(property="reason", type="string", example="個人休假")
     *             )),
     *             @OA\Property(property="pagination", type="object",
     *                 @OA\Property(property="total", type="integer", example=100),
     *                 @OA\Property(property="per_page", type="integer", example=10),
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="last_page", type="integer", example=10)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="無此權限，無法查詢請假紀錄",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="無此權限，無法查詢請假紀錄")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="查詢失敗，請稍後再試",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="系統發生錯誤，請稍後再試")
     *         )
     *     )
     * )
     */
    public function viewCompanyLeaveRecords(Request $request): JsonResponse
    {
        $user = auth()->user();
        if ($user->employee->status === 'inactive') {
            return response()->json(['message' => '無此權限，無法查詢請假紀錄'], 403);
        }

        try {
            // ✅ 查詢所有請假紀錄
            $filters = $request->validate([
                'leave_type_id' => 'nullable|exists:leave_types,id',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'status' => 'nullable|integer|in:0,1,2,3,4',
            ]);

            $leaves = $this->leaveService->getCompanyLeaveList($filters);
            $formattedRecords = $leaves->map(fn($leave) => $this->formatLeave($leave));
            return response()->json([
                'message' => '查詢成功',
                'records' => $formattedRecords,
                'pagination' => [
                    'total' => $leaves->total(),
                    'per_page' => $leaves->perPage(),
                    'current_page' => $leaves->currentPage(),
                    'last_page' => $leaves->lastPage(),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => '查詢失敗，請稍後再試',
            ], 500);
        }
    }

    // 5. 修改請假申請 (HR、員工)
    /**
     * @OA\Post(
     *     path="/api/leave/update/{id}",
     *     summary="更新請假紀錄",
     *     description="更新指定的請假單資料，包括假別、時間、原因及附件。",
     *     operationId="updateLeave",
     *     tags={"Leave"},
     *     security={{"bearerAuth":{}}},
     * 
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="請假單 ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     * 
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"start_time", "end_time", "leave_type", "reason"},
     *                 @OA\Property(property="start_time", type="string", format="date-time", example="2024-03-15 09:00:00", description="請假開始時間"),
     *                 @OA\Property(property="end_time", type="string", format="date-time", example="2024-03-15 18:00:00", description="請假結束時間"),
     *                 @OA\Property(property="leave_type", type="integer", example=1, description="請假類型 ID"),
     *                 @OA\Property(property="reason", type="string", example="身體不適", description="請假原因"),
     *                 @OA\Property(property="attachment", type="string", format="binary", description="可選的請假附件 (最大 10MB)"),
     *             )
     *         )
     *     ),
     * 
     *     @OA\Response(
     *         response=200,
     *         description="假單更新成功",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="假單更新成功"),
     *             @OA\Property(property="leave", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="start_time", type="string", format="date-time", example="2024-03-15 09:00:00"),
     *                 @OA\Property(property="end_time", type="string", format="date-time", example="2024-03-15 18:00:00"),
     *                 @OA\Property(property="leave_type", type="string", example="病假"),
     *                 @OA\Property(property="reason", type="string", example="身體不適"),
     *                 @OA\Property(property="attachment", type="string", example="file_id_123"),
     *                 @OA\Property(property="status", type="integer", example=2),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2024-03-15T10:00:00Z")
     *             )
     *         )
     *     ),
     * 
     *     @OA\Response(
     *         response=403,
     *         description="無權限修改此請假單或查無此假單",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="查無此假單或您無權限修改")
     *         )
     *     ),
     * 
     *     @OA\Response(
     *         response=422,
     *         description="請求格式錯誤",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="更新失敗，請重新檢查資料格式是否錯誤"),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="start_time", type="array",
     *                     @OA\Items(type="string", example="請假開始時間為必填")
     *                 ),
     *                 @OA\Property(property="leave_type", type="array",
     *                     @OA\Items(type="string", example="請假類型 ID 無效")
     *                 )
     *             )
     *         )
     *     ),
     * 
     *     @OA\Response(
     *         response=500,
     *         description="伺服器錯誤",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="更新失敗，請重新檢查資料格式是否錯誤")
     *         )
     *     )
     * )
     */
    public function updateLeave(LeaveUpdateRequest $request, int $id): JsonResponse
    {
        try {
            $user = auth()->user();

            // 1️⃣ 取得請假紀錄
            $leave = Leave::where('id', $id)
                ->where('user_id', $user->id)
                ->first();

            if (!$leave) {
                return response()->json(['message' => '查無此假單或您無權限修改'], 403);
            }

            // 2️⃣ **處理附件**（如果沒有新附件，保持原本的 `attachment_id`）
            $fileRecord = null;
            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');

                // 產生唯一檔名
                $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
                $attachmentPath = $file->storeAs('attachments', $filename, 'public');

                // 取得舊附件
                $oldFile = File::find($leave->attachment);

                // **刪除舊附件檔案**
                if ($oldFile && Storage::exists($oldFile->leave_attachment)) {
                    Storage::delete($oldFile->leave_attachment);
                    Log::info("成功刪除舊附件: " . $oldFile->leave_attachment);
                }

                // **更新舊 `File` 紀錄，或新增新附件**
                if ($oldFile) {
                    $oldFile->update(['leave_attachment' => $attachmentPath]);
                    $fileRecord = $oldFile;
                } else {
                    $fileRecord = File::create([
                        'user_id' => $user->id,
                        'leave_id' => $leave->id,
                        'leave_attachment' => $attachmentPath,
                    ]);
                }
            }

            // 3️⃣ **呼叫 Service 層更新請假**
            $updatedLeave = $this->leaveService->updateLeave($leave, [
                'leave_type' => $request->input('leave_type'),
                'start_time' => $request->input('start_time'),
                'end_time' => $request->input('end_time'),
                'reason' => $request->input('reason'),
                'status' => $request->input('status'),
                'attachment' => $fileRecord ? $fileRecord->id : $leave->attachment, // **如果有新附件就更新，否則保持原值**
            ]);

            // 4️⃣ **回傳成功訊息**
            return response()->json([
                'message' => '假單更新成功',
                'leave' => $this->formatLeave($updatedLeave),
            ], 200);
        } catch (\Exception $e) {
            // 5️⃣ **回傳錯誤訊息**
            return response()->json([
                'message' => app()->isLocal() ? $e->getMessage() : '更新失敗，請重新檢查資料格式是否錯誤',
            ], 500);
        }
    }


    // 6. 刪除請假申請 (HR、員工)
    /**
     * @OA\Delete(
     *     path="/api/leave/{id}",
     *     summary="刪除請假申請",
     *     description="根據請假 ID 刪除請假申請，並同時刪除相關附件。",
     *     operationId="deleteLeave",
     *     tags={"Leave"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="請假申請的 ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="成功刪除假單",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="假單刪除成功")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="查無此假單或無權限刪除",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="查無此假單或您無權限刪除")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="伺服器內部錯誤",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="系統發生錯誤，請稍後再試")
     *         )
     *     )
     * )
     */
    public function deleteLeave(int $id): JsonResponse
    {
        try {
            $user = auth()->user();  // 取得當前登入的使用者

            // 1️⃣ **取得請假紀錄**
            $leave = Leave::find($id);

            if (!$leave) {
                Log::warning("刪除請假失敗 - 找不到假單或無權限", ['user_id' => $user->id, 'leave_id' => $id]);
                return response()->json(['message' => '查無此假單或您無權限刪除'], 403);
            }

            // 2️⃣ **刪除相關附件**
            if (!empty($leave->attachment)) {
                $file = File::find($leave->attachment);
                if ($file) {
                    $filePath = $file->leave_attachment;

                    // **刪除實體檔案**
                    if ($filePath && Storage::exists($filePath)) {
                        Storage::delete($filePath);
                        Log::info("成功刪除附件檔案: " . $filePath);
                    }

                    // **刪除 `files` 表中的紀錄**
                    $file->delete();
                    // Log::info("成功刪除 files 記錄", ['file_id' => $file->id]);
                }
            }

            // 3️⃣ **刪除請假申請**
            $leave->delete();
            // Log::info("成功刪除假單", ['user_id' => $user->id, 'leave_id' => $id]);

            // 4️⃣ **回傳成功訊息**
            return response()->json(['message' => '假單刪除成功'], 200);
        } catch (\Exception $e) {
            // 5️⃣ **回傳錯誤訊息**
            return response()->json([
                'message' => app()->isLocal() ? $e->getMessage() : '系統發生錯誤，請稍後再試',
            ], 500);
        }
    }

    // 7. 取得特殊假別剩餘小時數 (計算假夠不夠扣)
    /**
     * @OA\Get(
     *     path="/api/leavetypes/hours/{leaveTypeId}",
     *     summary="取得特殊假別剩餘小時數",
     *     description="計算使用者的特定假別剩餘時數，確認是否足夠扣除。",
     *     operationId="getRemainingLeaveHours",
     *     tags={"Leave"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="leaveTypeId",
     *         in="path",
     *         required=true,
     *         description="假別類型的 ID",
     *         @OA\Schema(type="integer", example=4)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="成功取得剩餘假別小時數",
     *         @OA\JsonContent(
     *             @OA\Property(property="remaining_hours", type="integer", example=8)
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="請求錯誤（例如無效的假別 ID）",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="無效的假別 ID")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="未授權或無權存取該假別資訊",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="您無權查看此假別資訊")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="伺服器內部錯誤",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="系統發生錯誤，請稍後再試")
     *         )
     *     )
     * )
     */
    public function getRemainingLeaveHours($leaveTypeId)
    {
        try {
            $user = auth()->user(); // 取得當前用戶
            $remainingHours = $this->leaveService->getRemainingLeaveHours($leaveTypeId, $user->id);

            return response()->json([
                'remaining_hours' => $remainingHours,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 500);
        }
    }

    // 8. 資料格式統一，讓回傳結果都長一樣 ✅    
    private function formatLeave($leave): array
    {
        return [
            'leave_id' => $leave->id,
            'user_id' => $leave->user_id,
            'user_name' => $leave->user->name,
            'leave_type' => optional($leave->leaveType)->name, // 確保讀取關聯名稱
            'start_time' => $leave->start_time,
            'end_time' => $leave->end_time,
            'reason' => $leave->reason,
            'status' => $leave->status,
            'attachment' => $leave->file ? asset("storage/" . $leave->file->leave_attachment) : null,
        ];
    }

    // 9.部門主管審核通過
    public function approveDepartmentLeave(Request $request, $id)
    {
        $leave = Leave::find($id);
        if (!$leave) {
            return response()->json(['error' => '查無此請假單'], 404);
        }

        $user = auth()->user();
        if (!$user) {
            return response()->json(['error' => '未授權請求，請先登入'], 401);
        }

        // 確保使用者擁有 `approve_department_leave` 權限
        if (!$user->can('approve_department_leave')) {
            return response()->json(['error' => '你沒有權限審核本部門請假單'], 403);
        }

        // 找出請假員工
        $leaveEmployee = Employee::where('user_id', $leave->user_id)->first();
        if (!$leaveEmployee) {
            return response()->json(['error' => '請假單存在，但查無該員工'], 404);
        }

        // 確保主管只能審核自己部門的員工
        $departmentId = Employee::where('user_id', $user->id)->value('department_id');
        if ($departmentId !== $leaveEmployee->department_id) {
            return response()->json(['error' => '你只能審核自己部門的員工'], 403);
        }

        // 確保請假單是待審核狀態
        if ($leave->status !== 0) {
            return response()->json(['error' => '此假單已審核，無法修改'], 403);
        }

        // 更新狀態為 主管批准
        $leave->status = 1;
        $leave->approved_by = $user->id;
        $leave->save();

        return response()->json(['message' => '假單已被主管審核，等待 HR 審核'], 200);
    }

    // 10.部門主管審核拒絕
    public function rejectDepartmentLeave(Request $request, $id)
    {
        $leave = Leave::find($id);
        if (!$leave) {
            return response()->json(['error' => '查無此請假單'], 404);
        }

        $user = auth()->user();
        if (!$user) {
            return response()->json(['error' => '未授權請求，請先登入'], 401);
        }

        // 確保使用者擁有 `approve_department_leave` 權限
        if (!$user->can('approve_department_leave')) {
            return response()->json(['error' => '你沒有權限駁回請假單'], 403);
        }

        // 找出請假員工
        $leaveEmployee = Employee::where('user_id', $leave->user_id)->first();
        if (!$leaveEmployee) {
            return response()->json(['error' => '請假單存在，但查無該員工'], 404);
        }

        // 確保主管只能審核自己部門的員工
        $departmentId = Employee::where('user_id', $user->id)->value('department_id');
        if ($departmentId !== $leaveEmployee->department_id) {
            return response()->json(['error' => '你只能拒絕自己部門的員工'], 403);
        }

        // 確保請假單是待審核狀態
        if ($leave->status !== 0) {
            return response()->json(['error' => '此假單已審核，無法修改'], 403);
        }

        // 確保拒絕理由存在且不是空字串
        $rejectReason = trim($request->input('reject_reason'));
        if (empty($rejectReason)) {
            return response()->json(['error' => '請填寫拒絕原因'], 400);
        }

        // 更新狀態為 主管拒絕
        $leave->status = 2;
        $leave->reject_reason = $rejectReason;
        $leave->approved_by = $user->id;
        $leave->save();

        return response()->json(['message' => '假單已被主管拒絕'], 200);
    }

    // 11.HR審核通過
    public function approveLeave(Request $request, $id)
    {
        $leave = Leave::find($id);
        if (!$leave) {
            return response()->json(['error' => '請假單不存在'], 404);
        }

        $user = auth()->user();
        if (!$user) {
            return response()->json(['error' => '未授權請求，請先登入'], 401);
        }

        // 確保使用者擁有 `approve_leave` 權限
        if (!$user->can('approve_leave')) {
            return response()->json(['error' => '你沒有權限最終批准假單'], 403);
        }

        // 假單如果已經由主管拒絕，就顯示 此假單主管已拒絕不可審核
        if ($leave->status === 2) {
            return response()->json(['error' => '此假單主管已拒絕不可審核'], 403);
        }

        // 確保請假單尚未被批准
        if ($leave->status === 3 || $leave->status === 4) {
            return response()->json(['error' => '此假單已被批准，不可重複審核'], 403);
        }

        // 確保請假單已經經過主管審核
        if ($leave->status !== 1) {
            return response()->json(['error' => '請假單必須先經過主管審核'], 403);
        }

        // 更新狀態為 HR 批准
        $leave->status = 3;
        $leave->approved_by = $user->id;
        $leave->save();

        return response()->json(['message' => '假單已最終批准'], 200);
    }

    // 12.HR審核拒絕
    public function rejectLeave(Request $request, $id)
    {
        $leave = Leave::find($id);
        if (!$leave) {
            return response()->json(['error' => '請假單不存在'], 404);
        }

        $user = auth()->user();
        if (!$user) {
            return response()->json(['error' => '未授權請求，請先登入'], 401);
        }

        // 確保使用者擁有 `approve_leave` 權限
        if (!$user->can('approve_leave')) {
            return response()->json(['error' => '你沒有權限駁回假單'], 403);
        }

        // 假單如果已經由主管拒絕，就顯示 此假單主管已拒絕不可審核
        if ($leave->status === 2) {
            return response()->json(['error' => '此假單主管已拒絕不可審核'], 403);
        }

        // 確保請假單尚未被批准
        if ($leave->status === 3 || $leave->status === 4) {
            return response()->json(['error' => '此假單已被批准，不可重複審核'], 403);
        }

        // 確保請假單已經經過主管審核
        if ($leave->status !== 1) {
            return response()->json(['error' => '請假單必須先經過主管審核'], 403);
        }

        // 確保拒絕理由存在且非空白字串
        $rejectReason = trim($request->input('reject_reason'));
        if (empty($rejectReason)) {
            return response()->json(['error' => '請填寫拒絕原因'], 400);
        }

        // 更新狀態為 HR 拒絕
        $leave->status = 4;
        $leave->reject_reason = $rejectReason;
        $leave->approved_by = $user->id;
        $leave->save();

        return response()->json(['message' => '假單已被 HR 拒絕'], 200);
    }
}

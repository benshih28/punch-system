<?php

namespace App\Http\Controllers;

use App\Http\Requests\LeaveApplyRequest;
use App\Http\Requests\LeaveListRequest;
use App\Http\Requests\LeaveUpdateRequest;
use App\Http\Requests\LeaveDeleteRequest;
use App\Services\LeaveService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Models\Leave;
use App\Models\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;


class LeaveController extends Controller
{
    protected $leaveService;

    public function __construct(LeaveService $leaveService)
    {
        $this->leaveService = $leaveService;
    }

    // 1. 申請請假
    public function requestLeave(LeaveApplyRequest $request): JsonResponse
    {
        try {
            // 1️⃣ 透過 JWT 取得當前登入者
            $user = auth()->user();

            // 2️⃣ **資料驗證**
            $data = $request->validated();
            $data['user_id'] = $user->id; // 由後端自動填入 `user_id`

            // 3️⃣ **處理附件** (確保 `attachment` 傳到 Service 層)
            if ($request->hasFile('attachment')) {
                $data['attachment'] = $request->file('attachment');
            }

            // 4️⃣ **呼叫 Service 層處理請假**
            $leave = $this->leaveService->applyLeave($data);
            $leave->load('user'); // **順便帶出 `user` 資料**

            // 6️⃣ **回傳成功資訊**
            return response()->json([
                'message' => '申請成功，假單已送出',
                'leave' => $this->formatLeave($leave),
            ], 201); // **201 Created：表示成功建立新資源**

        } catch (\Throwable $e) { // ❗ 改用 `Throwable` 可捕獲所有錯誤 (Exception + Error)
            // 7️⃣ **記錄錯誤日誌**
            Log::error('❌ 請假申請失敗', [
                'user_id' => $user->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // 8️⃣ **回傳錯誤資訊**
            return response()->json([
                'message' => '申請失敗，請稍後再試或聯繫管理員',
                'error' => app()->isLocal() ? $e->getMessage() : null, // **本機開發環境才回傳錯誤**
            ], 500); // **500 Internal Server Error**
        }
    }

    // 2. 查詢個人請假紀錄
    public function viewMyLeaveRecords(LeaveListRequest $request): JsonResponse
    {
        try {
            $user = auth()->user();
            $filters = $request->validated();

            Log::info('查詢請假紀錄', ['user_id' => $user->id, 'filters' => $filters]);

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
            Log::error('查詢請假紀錄失敗', [
                'user_id' => auth()->user()->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => app()->isLocal() ? $e->getMessage() : '系統發生錯誤，請稍後再試',
            ], 500);
        }
    }

    // 3. 查詢「部門」請假紀錄（限主管 & HR）
    public function viewDepartmentLeaveRecords(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return response()->json(['message' => '未登入或無權限'], 401);
            }

            $filters = $request->validate([
                'leave_type_id' => 'nullable|exists:leave_types,id',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
                'status' => 'nullable|in:pending,approved,rejected',
            ]);

            Log::info('查詢部門請假紀錄', ['user_id' => $user->id, 'filters' => $filters]);

            $leaves = $this->leaveService->getDepartmentLeaveList($user, $filters);

            if ($leaves->isEmpty()) {
                return response()->json([
                    'message' => '查無符合條件的請假紀錄',
                    'records' => [],
                ], 204);
            }

            return response()->json([
                'message' => '查詢成功',
                'records' => $leaves->map(fn($leave) => $this->formatLeave($leave)),
            ], 200);
        } catch (\Exception $e) {
            Log::error('部門請假查詢失敗', [
                'user_id' => $user->id ?? 'N/A',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => app()->isLocal() ? $e->getMessage() : '系統發生錯誤，請稍後再試',
            ], 500);
        }
    }

    // 4. HR查詢全公司請假紀錄
    public function viewCompanyLeaveRecords(LeaveListRequest $request): JsonResponse
    {
        try {
            $filters = $request->validated();
            Log::info('接收到的篩選條件', ['filters' => $filters]);

            // ✅ 查詢所有請假紀錄，使用分頁
            $leaves = $this->leaveService->getCompanyLeaveList($filters, 15);

            Log::info('查詢結果', ['total' => $leaves->total(), 'data' => $leaves->items()]);

            return response()->json([
                'message' => '查詢成功',
                'records' => $leaves->items(),  // 只傳當前分頁的紀錄
                'pagination' => [
                    'total' => $leaves->total(),
                    'per_page' => $leaves->perPage(),
                    'current_page' => $leaves->currentPage(),
                    'last_page' => $leaves->lastPage(),
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('全公司請假查詢失敗', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => app()->isLocal() ? $e->getMessage() : '系統發生錯誤，請稍後再試',
            ], 500);
        }
    }

    // 5. 修改請假申請
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

            // 2️⃣ 使用 Service 層更新請假
            $updatedLeave = $this->leaveService->updateLeave($leave, $request->validated());

            // 3️⃣ 回傳成功訊息
            return response()->json([
                'message' => '假單更新成功',
                'leave' => $this->formatLeave($updatedLeave),
            ], 200);
        } catch (\Exception $e) {
            // 4️⃣ 記錄錯誤日誌
            Log::error('更新請假單失敗', [
                'user_id' => auth()->user()->id ?? null,
                'leave_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // 5️⃣ 回傳錯誤訊息
            return response()->json([
                'message' => app()->isLocal() ? $e->getMessage() : '系統發生錯誤，請稍後再試',
            ], 500);
        }
    }

    // 6. 刪除請假申請
    public function deleteLeave(int $id): JsonResponse
    {
        try {
            $user = auth()->user();  // 取得當前登入的使用者

            // 1️⃣ 取得請假紀錄，確保該假單屬於當前用戶
            $leave = Leave::where('id', $id)
                ->where('user_id', $user->id)
                ->first();

            if (!$leave) {
                Log::warning("刪除請假失敗 - 找不到假單或無權限", ['user_id' => $user->id, 'leave_id' => $id]);
                return response()->json(['message' => '查無此假單或您無權限刪除'], 403);
            }

            // 2️⃣ **刪除相關附件**
            $file = File::where('id', $leave->attachment)->first();
            if ($file) {
                $filePath = $file->leave_attachment;

                // **刪除實體檔案**
                if ($filePath && Storage::disk('public')->exists($filePath)) {
                    Storage::disk('public')->delete($filePath);
                    Log::info("成功刪除附件檔案: " . $filePath);
                }

                // **刪除 `files` 表中的紀錄**
                $file->delete();
                Log::info("成功刪除 files 記錄", ['file_id' => $file->id]);
            }

            // 3️⃣ **刪除請假申請**
            $leave->delete();
            Log::info("成功刪除假單", ['user_id' => $user->id, 'leave_id' => $id]);

            // 4️⃣ **回傳成功訊息**
            return response()->json(['message' => '假單刪除成功'], 200);
        } catch (\Exception $e) {
            // 5️⃣ **異常處理，記錄錯誤**
            Log::error('刪除請假申請失敗', [
                'user_id' => auth()->user()->id ?? null,
                'leave_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // 6️⃣ **回傳錯誤訊息**
            return response()->json([
                'message' => app()->isLocal() ? $e->getMessage() : '系統發生錯誤，請稍後再試',
            ], 500);
        }
    }

    // 7. 取得特殊假別剩餘小時數
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

    // ✅ 資料格式統一，讓回傳結果都長一樣
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
            'attachment' => $leave->attachment ? asset('storage/' . $leave->attachment) : null,
        ];
    }
}

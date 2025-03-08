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

class LeaveController extends Controller
{
    protected $leaveService;

    public function __construct(LeaveService $leaveService)
    {
        $this->leaveService = $leaveService;
    }

    // 1. 申請請假
    public function leaveApply(LeaveApplyRequest $request): JsonResponse
    {
        Log::info('leaveApply進來了', $request->all());

        $user = auth()->user();  // 透過JWT取得當前登入者

        $data = $request->validated(); // 先做欄位驗證，通過後再繼續
        $data['user_id'] = auth()->user()->id;  // user_id由後端自動補，不讓前端傳

        // ✅ 把attachment傳進Service
        if ($request->hasFile('attachment')) {
            $data['attachment'] = $request->file('attachment');
        }

        try {
            $leave = $this->leaveService->applyLeave($data); // 交給Service處理申請邏輯
            $leave->load('user');                            // 帶出user關聯資料

            Log::info('申請成功', ['leave_id' => $leave->id]);

            // ✅ 成功回傳
            return response()->json([
                'message' => '申請成功，假單已送出',
                'leave' => $this->formatLeave($leave),
            ], 200);
        } catch (\Exception $e) {
            // 📝 Log完整錯誤資訊
            Log::error('【請假申請失敗】', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // ❗回傳錯誤訊息
            return response()->json([
                'message' => '申請失敗，請稍後再試或聯繫管理員',
                'error' => env('APP_DEBUG') ? $e->getMessage() : null,  // 本機才吐錯，正式不顯示細節
            ], 400);
        }
    }

    // 2. 查詢個人請假紀錄
    public function personalLeaveList(LeaveListRequest $request): JsonResponse
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
    public function departmentLeaveRecords(LeaveListRequest $request): JsonResponse
    {
        try {
            $user = auth()->user();

            if (!$user->hasPermission('view_department_leaves')) {
                return response()->json(['message' => '您無權查詢部門請假紀錄'], 403);
            }

            $filters = $request->validated();
            Log::info('查詢部門請假紀錄', ['user_id' => $user->id, 'filters' => $filters]);

            // 只查詢 **同部門** 的請假紀錄
            $leaves = $this->leaveService->getDepartmentLeaveList($user, $filters);

            if ($leaves->isEmpty()) {
                return response()->json([
                    'message' => '查無符合條件的請假紀錄',
                    'records' => [],
                ], 200);
            }

            return response()->json([
                'message' => '查詢成功',
                'records' => $leaves->map(fn($leave) => $this->formatLeave($leave)),
            ], 200);
        } catch (\Exception $e) {
            Log::error('部門請假查詢失敗', [
                'user_id' => auth()->user()->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => app()->isLocal() ? $e->getMessage() : '系統發生錯誤，請稍後再試',
            ], 500);
        }
    }

    // 3. HR查詢全公司請假紀錄
    public function companyLeaveRecords(LeaveListRequest $request): JsonResponse
    {
        try {
            $user = auth()->user();

            if (!$user->hasPermission('view_company_leaves')) {
                return response()->json(['message' => '您無權查詢全公司請假紀錄'], 403);
            }

            $filters = $request->validated();
            Log::info('查詢全公司請假紀錄', ['user_id' => $user->id, 'filters' => $filters]);

            // 查詢 **所有人** 的請假紀錄
            $leaves = $this->leaveService->getCompanyLeaveList($filters)->paginate(15);

            if ($leaves->isEmpty()) {
                return response()->json([
                    'message' => '查無符合條件的請假紀錄',
                    'records' => [],
                ], 200);
            }

            return response()->json([
                'message' => '查詢成功',
                'records' => $leaves->map(fn($leave) => $this->formatLeave($leave)),
            ], 200);
        } catch (\Exception $e) {
            Log::error('全公司請假查詢失敗', [
                'user_id' => auth()->user()->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => app()->isLocal() ? $e->getMessage() : '系統發生錯誤，請稍後再試',
            ], 500);
        }
    }

    // 4. 修改請假申請
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

    // 4. 刪除請假申請
    public function leaveApplyDelete(int $id): JsonResponse
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

    // 5. 取得特殊假別剩餘小時數
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

<?php

namespace App\Http\Controllers;

use App\Http\Requests\LeaveApplyRequest;
use App\Http\Requests\LeaveListRequest;
use App\Http\Requests\LeaveUpdateRequest;
use App\Http\Requests\LeaveDeleteRequest;
use App\Services\LeaveService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

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
        $user = auth()->user();  // 透過JWT取得當前登入者

        $data = $request->validated(); // 先做欄位驗證，通過後再繼續
        $data['user_id'] = $user->id;  // user_id由後端自動補，不讓前端傳

        // ✅ 把attachment傳進Service
        if ($request->hasFile('attachment')) {
            $data['attachment'] = $request->file('attachment');
        } else {
            $data['attachment'] = null;  // 保底，避免Service收到空資料
        }

        try {
            $leave = $this->leaveService->applyLeave($data); // 交給Service處理申請邏輯
            $leave->load('user');                            // 帶出user關聯資料

            Log::info('申請請假', ['user_id' => $user->id, 'filters' => $filters]);

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
            ], 500);
        }
    }

    // 2. 查詢請假紀錄（帶角色權限判斷）
    public function leaveRecords(LeaveListRequest $request): JsonResponse
    {
        try {
            $user = auth()->user();
            $filters = $request->validated();

            Log::info('查詢請假紀錄', ['user_id' => $user->id, 'filters' => $filters]);

            $leaves = $this->leaveService->getLeaveList($user, $filters);

            if ($leaves->isEmpty()) {
                return response()->json([
                    'status' => 'success',
                    'message' => '查無資料，請重新選擇日期區間或是假別',
                    'records' => [],
                ], 200);
            }

            return response()->json([
                'status' => 'success',
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
                'status' => 'error',
                'message' => app()->isLocal() ? $e->getMessage() : '系統發生錯誤，請稍後再試',
            ], 500);
        }
    }

    // 3. 修改請假原因功能
    // 3-1. 單筆查詢（帶角色權限判斷）
    public function showLeave(int $id): JsonResponse
    {
        try {
            $user = auth()->user();

            Log::info('單筆請假查詢', ['user_id' => $user->id, 'leave_id' => $id]);

            $leave = $this->leaveService->getSingleLeave($user, $id);

            if (!$leave) {
                return response()->json(['message' => '查無此假單'], 403);
            }

            return response()->json([
                'message' => '查詢成功',
                'leave' => $this->formatLeave($leave),
            ], 200);
        } catch (\Exception $e) {
            Log::error('單筆請假查詢失敗', [
                'user_id' => auth()->user()->id,
                'leave_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'status' => 'error',
                'message' => app()->isLocal() ? $e->getMessage() : '系統發生錯誤，請稍後再試',
            ], 500);
        }
    }

    // 3-2. 更新請假單（帶角色權限判斷）
    public function updateLeave(LeaveUpdateRequest $request, int $id): JsonResponse
    {
        try {
            $user = auth()->user();

            Log::info('更新請假單', [
                'user_id' => $user->id,
                'leave_id' => $id,
                'data' => $request->all(),
            ]);

            $leave = $this->leaveService->getSingleLeave($user, $id);

            if (!$leave) {
                return response()->json(['message' => '查無此假單'], 403);
            }

            $this->leaveService->updateLeave($leave, $request->validated());

            return response()->json(['message' => '假單更新成功'], 200);

        } catch (\Exception $e) {
            Log::error('更新請假單失敗', [
                'user_id' => auth()->user()->id,
                'leave_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => app()->isLocal() ? $e->getMessage() : '系統發生錯誤，請稍後再試',
            ], 500);
        }
    }

    // ✅ 資料格式統一，讓回傳結果都長一樣
    private function formatLeave($leave): array
    {
        return [
            'leave_id' => $leave->id,
            'user_id' => $leave->user_id,
            'user_name' => $leave->user->name,
            'leave_type' => $leave->leave_type,
            'start_time' => $leave->start_time,
            'end_time' => $leave->end_time,
            'reason' => $leave->reason,
            'status' => $leave->status,
            'attachment' => $leave->attachment ? asset('storage/' . $leave->attachment) : null,
        ];
    }

    // 4. 刪除請假申請
    // public function delete(LeaveDeleteRequest $request, Leave $leave): JsonResponse
    // {
    //     $leave->delete();
    //     return response()->json(['success' => true]);
    // }
}

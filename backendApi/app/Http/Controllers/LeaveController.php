<?php

namespace App\Http\Controllers;

use App\Http\Requests\LeaveApplyRequest;
use App\Http\Requests\LeaveListRequest;
use App\Http\Requests\LeaveUpdateRequest;
use App\Http\Requests\LeaveDeleteRequest;
use App\Models\Leave;
use App\Services\LeaveService;
use Illuminate\Http\JsonResponse;

class LeaveController extends Controller
{
    protected $leaveService;

    public function __construct(LeaveService $leaveService)
    {
        $this->leaveService = $leaveService;
    }

    // 申請請假
    public function leaveApply(LeaveApplyRequest $request): JsonResponse
    {
        $user = auth()->user();  // 透過JWT取得當前登入者

        $data = $request->validated(); // 先做欄位驗證，通過後再繼續
        $data['user_id'] = $user->id;  // user_id由後端自動補，不讓前端傳

        // ✅ 把attachment傳進Service
        if ($request->hasFile('attachment')) {
            $data['attachment'] = $request->file('attachment');
        }

        $leave = $this->leaveService->applyLeave($data); // 交給Service處理申請邏輯

        $leave->load('user');

        // 回傳成功
        return response()->json([
            'message' => '已送出申請',
            'leave_id' => $leave->id,
            'leave_apply' => [
                'user_id' => $leave->user_id,
                'user_name' => $leave->user->name,
                'leave_type' => $leave->leave_type,
                'start_time' => $leave->start_time,
                'end_time' => $leave->end_time,
                'reason' => $leave->reason,
                'status' => $leave->status,
                'attachment' => $leave->attachment
                    ? asset('storage/' . $leave->attachment)
                    : null,
            ],
        ], 201);  // 201 Created

    }

    // 查詢個人請假紀錄
    public function leaveRecords(LeaveListRequest $request): JsonResponse
    {
        $user = auth()->user();  // 取得登入者

        $filters = $request->validated();

        $leaves = $this->leaveService->getLeaveList($user->id, $filters);  // 撈清單

        // 如果完全沒資料，回傳"查無資料"
        if ($leaves->isEmpty()) {
            return response()->json([
                'message' => '查無資料，請重新選擇日期區間或是假別',
                'records' => [],
            ], 200);
        }

        // 格式化回傳，變成你想要的格式
        $records = $leaves->map(function ($leave) {
            return [
                'leave_id' => $leave->id,
                'user_id' => $leave->user_id,
                'user_name' => $leave->user->name,
                'leave_type' => $leave->leave_type,
                'start_time' => $leave->start_time,
                'end_time' => $leave->end_time,
                'reason' => $leave->reason,
                'status' => $leave->status,
                'attachment' => $leave->attachment
                    ? asset('storage/' . $leave->attachment)
                    : null,
            ];
        });

        return response()->json([
            'message' => '查詢成功',
            'records' => $records,
        ]);
    }

    // // 修改請假原因
    // public function update(LeaveUpdateRequest $request, Leave $leave): JsonResponse
    // {
    //     $this->leaveService->updateLeave($leave, $request->validated());
    //     return response()->json(['success' => true]);
    // }

    // // 刪除請假申請
    // public function delete(LeaveDeleteRequest $request, Leave $leave): JsonResponse
    // {
    //     $leave->delete();
    //     return response()->json(['success' => true]);
    // }
}

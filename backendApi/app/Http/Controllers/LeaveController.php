<?php

namespace App\Http\Controllers;

use App\Http\Requests\LeaveApplyRequest;
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
    public function apply(LeaveApplyRequest $request): JsonResponse
    {
        $user = auth()->user();  // 透過JWT取得當前登入者

        $data = $request->validated(); // 先做欄位驗證，通過後再繼續

        $data['user_id'] = $user->id;  // user_id由後端自動補，不讓前端傳

        $leave = $this->leaveService->applyLeave($data); // 交給Service處理申請邏輯

        // 回傳成功
        return response()->json([
            'success' => true,
            'leave_id' => $leave->id,
        ]);
    }

    // 查詢我的請假紀錄
    public function index(): JsonResponse
    {
        $leaves = Leave::where('user_id', auth()->id())->get();
        return response()->json($leaves);
    }

    // 修改請假
    public function update(LeaveUpdateRequest $request, Leave $leave): JsonResponse
    {
        $this->leaveService->updateLeave($leave, $request->validated());
        return response()->json(['success' => true]);
    }

    // 刪除請假
    public function delete(LeaveDeleteRequest $request, Leave $leave): JsonResponse
    {
        $leave->delete();
        return response()->json(['success' => true]);
    }
}

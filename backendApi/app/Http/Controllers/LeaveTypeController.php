<?php

namespace App\Http\Controllers;

use App\Helpers\LeaveHelper;
use Illuminate\Http\Request;
use App\Models\LeaveType;
use App\Http\Requests\LeaveTypeCreateRequest;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\LeaveTypeUpdateRequest;

class LeaveTypeController extends Controller
{
    // 1. 新增
    public function addLeaveTypes(LeaveTypeCreateRequest $request): JsonResponse
    {
        // 驗證輸入資料
        $validated = $request->validated();

        // 新增假別
        $leaveType = LeaveType::create([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'total_hours' => $validated['total_hours'] ?? null
        ]);        

        // 回傳新增結果
        return response()->json([
            'message' => '假別新增成功',
            'leave_type' => $leaveType,
        ], 201);
    }

    // 2. 刪除
    public function destroyLeaveTypes($id)
    {
        $leaveType = LeaveType::find($id);

        $leaveType->delete();

        return response()->json(['message' => '假別刪除成功'], 200);
    }

    // 3. 修改
    public function updateLeaveTypes($id, LeaveTypeUpdateRequest $request)
    {
        $leaveType = LeaveType::find($id);

        $validated = $request->validated();

        $leaveType->update([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'total_hours' => $validated['total_hours'] ?? null,
        ]);  

        return response()->json([
            'message' => '假別更新成功',
            'leave_type' => $leaveType,
        ], 200);
    }

    // 4. 取得所有假別(放在下拉式選單)
    public function getleaveTypes()
    {
        // 取得所有假別
        $leaveTypes = LeaveType::all();
        return response()->json($leaveTypes);
    }

    // 5. 取得所有狀態(放在下拉式選單)
    public function getleaveStatus()
    {
        // 取得所有假別狀態（來自 LeaveHelper）
        $leaveStatus = LeaveHelper::allLeaveStatuses();

        return response()->json($leaveStatus);
    }
}

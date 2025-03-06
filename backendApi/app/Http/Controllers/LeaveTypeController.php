<?php

namespace App\Http\Controllers;

use App\Helpers\LeaveHelper;
use Illuminate\Http\Request;
use App\Models\LeaveType;
use App\Http\Requests\LeaveTypeCreateRequest;

class LeaveTypeController extends Controller
{
    /**
     * 新增假別 (新增自訂假別或填充預設假別)，把預設弄掉
     */
    public function addLeaveType(LeaveTypeCreateRequest $request): JsonResponse
    {
        // 驗證輸入資料
        $validated = $request->validated();

        // 新增假別
        $leaveType = LeaveType::create([
            'name' => $validated['name'],
            'description' => $validated['description'],
        ]);

        // 回傳新增結果
        return response()->json([
            'message' => '假別新增成功',
            'leave_type' => $leaveType,
        ], 201);
    }


    // 2. 刪除假別
    public function leaveTypesDestroy($id)
    {
        $leaveType = LeaveType::find($id);

        // 如果該假別不存在，返回 404 錯誤
        if (!$leaveType) {
            return response()->json(['message' => '假別未找到'], 404);
        }

        // 如果是預設假別，則不能刪除
        if ($leaveType->is_system_default) {
            return response()->json(['message' => '預設假別無法刪除'], 403);
        }

        // 自訂假別可刪除
        $leaveType->delete();
        return response()->json(['message' => '假別刪除成功'], 200);
    }

    // 4. 所有假別(放在下拉式選單)
    public function getleaveTypes()
    {
        $leaveTypes = LeaveType::all();
        return response()->json($leaveTypes);
    }

    // 5. 所有狀態(放在下拉式選單)
    public function getleaveStatus()
    {
        // 取得所有假別狀態（來自 LeaveHelper）
        $leaveStatus = LeaveHelper::allLeaveStatuses();

        return response()->json($leaveStatus);
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\LeaveType;

class LeaveTypeController extends Controller
{
    /**
     * 1️⃣ 新增請假類型
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:leave_types,name',  // 英文名稱
            'description' => 'required|string|max:255',                   // 中文名稱
            'total_hours' => 'nullable|integer|min:0',                    // 時數
        ]);

        $leaveType = LeaveType::create($validated);

        return response()->json([
            'message' => '假別新增成功',
            'leave_type' => $leaveType,
        ], 201);
    }

    /**
     * 2️⃣ 修改請假類型
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $leaveType = LeaveType::find($id);

        if (!$leaveType) {
            return response()->json(['message' => '找不到該請假類型'], 404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:leave_types,name,' . $id,
            'description' => 'required|string|max:255',
            'total_hours' => 'nullable|integer|min:0',
        ]);

        $leaveType->update($validated);

        return response()->json([
            'message' => '假別更新成功',
            'leave_type' => $leaveType,
        ], 200);
    }

    /**
     * 3️⃣ 刪除請假類型
     */
    public function destroy(int $id): JsonResponse
    {
        $leaveType = LeaveType::find($id);

        if (!$leaveType) {
            return response()->json(['message' => '找不到該請假類型'], 404);
        }

        $leaveType->delete();

        return response()->json(['message' => '假別刪除成功'], 200);
    }

    /**
     * 4️⃣ 取得所有請假類型（放在下拉式選單）
     */
    public function index(): JsonResponse
    {
        return response()->json(LeaveType::all());
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\LeaveResetRule;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class LeaveRuleController extends Controller
{
    // ✅ 新增假規
    public function addLeaveRule(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'leave_type_id' => 'required|exists:leave_types,id',
            'rule_type' => 'required|in:yearly,monthly',
            'rule_value' => 'nullable|string|max:20',
        ]);

        $rule = LeaveResetRule::create($validated);

        return response()->json([
            'message' => '新增成功',
            'rule' => $rule,
        ], 201);
    }

    // ✅ 修改假規
    public function updateLeaveRule(Request $request, $id): JsonResponse
    {
        $validated = $request->validate([
            'leave_type_id' => 'required|exists:leave_types,id',
            'rule_type' => 'required|in:yearly,monthly',
            'rule_value' => 'nullable|string|max:20',
        ]);

        $rule = LeaveResetRule::findOrFail($id);
        $rule->update($validated);

        return response()->json([
            'message' => '更新成功',
            'rule' => $rule,
        ], 200);
    }

    // ✅ 查詢全部假規
    public function getLeaveRules(): JsonResponse
    {
        $rules = LeaveResetRule::with('leaveType')->get();

        return response()->json([
            'message' => '取得所有請假規則成功',
            'rules' => $rules,
        ], 200);
    }

    // ✅ 刪除假規
    public function destroyLeaveRule($id): JsonResponse
    {
        $rule = LeaveResetRule::findOrFail($id);
        $rule->delete();

        return response()->json([
            'message' => '刪除成功',
        ], 200);
    }
}

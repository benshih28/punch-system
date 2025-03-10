<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LeaveResetRule;
use Illuminate\Http\JsonResponse;

class LeaveResetRuleController extends Controller
{
    // 1. 新增假規
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'leave_type_id' => 'required|exists:leave_types,id',
            'rule_type' => 'required|in:yearly,monthly',
            'rule_value' => 'nullable|string|max:20',     // 例如 "01-01" 或 "15"
        ]);

        $rule = LeaveResetRule::create($validated);

        return response()->json([
            'message' => '新增成功',
            'rule' => $rule,
        ], 201);
    }

    // 2. 更新假規
    public function update(Request $request, int $id): JsonResponse
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

    // 3. 查詢所有假規
    public function index(): JsonResponse
    {
        return response()->json(LeaveResetRule::with('leaveType')->get(), 200);
    }

    // 4. 刪除假規
    public function destroy($id): JsonResponse
    {
        $rule = LeaveResetRule::findOrFail($id);
        $rule->delete();

        return response()->json([
            'message' => '刪除成功',
        ], 200);
    }
}
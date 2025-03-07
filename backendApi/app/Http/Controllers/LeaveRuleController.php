<?php

namespace App\Http\Controllers;

use App\Http\Requests\LeaveRuleRequest;
use App\Models\LeaveResetRule;
use App\Services\LeaveResetService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class LeaveRuleController extends Controller
{
    //
    protected $leaveResetService;   // ✅ 宣告service

    public function __construct(LeaveResetService $leaveResetService)
    {
        $this->leaveResetService = $leaveResetService;  // ✅ 注入進來
    }
    
    public function index(): JsonResponse
    {
        $rules = LeaveResetRule::with('leaveType')->get();

        return response()->json($rules);
    }

    public function store(LeaveRuleRequest $request): JsonResponse
    {
        $rule = LeaveResetRule::create($request->validated());

        return response()->json([
            'message' => '請假規則新增成功',
            'rule' => $rule,
        ]);
    }

    public function update(LeaveRuleRequest $request, $id): JsonResponse
    {
        $rule = LeaveResetRule::findOrFail($id);
        $rule->update($request->validated());

        return response()->json([
            'message' => '請假規則更新成功',
            'rule' => $rule,
        ]);
    }

    public function destroy($id): JsonResponse
    {
        $rule = LeaveResetRule::findOrFail($id);
        $rule->delete();

        return response()->json(['message' => '請假規則刪除成功']);
    }

}

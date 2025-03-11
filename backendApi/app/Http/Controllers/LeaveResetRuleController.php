<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LeaveResetRule;
use Illuminate\Http\JsonResponse;

class LeaveResetRuleController extends Controller
{
    // 1. 新增假規
    /**
     * @OA\Post(
     *     path="/api/leavetypes/rules/add",
     *     summary="新增假規",
     *     description="此 API 允許新增假規。",
     *     tags={"Leave Reset Rules"},
     *     security={{ "bearerAuth": {} }},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="leave_type_id", type="integer", example=1, description="對應的假別類型 ID"),
     *             @OA\Property(property="rule_type", type="string", example="yearly", description="假規類型，可選 `yearly` 或 `monthly`"),
     *             @OA\Property(property="rule_value", type="string", example="01-01", description="假規值，例如 '01-01' 代表每年 1 月 1 日重置")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="假規新增成功",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="新增成功"),
     *             @OA\Property(property="rule", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="leave_type_id", type="integer", example=1),
     *                 @OA\Property(property="rule_type", type="string", example="yearly"),
     *                 @OA\Property(property="rule_value", type="string", example="01-01")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="請求資料驗證失敗",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="請提供有效的 leave_type_id")
     *         )
     *     )
     * )
     */

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
    /**
     * @OA\Patch(
     *     path="/api/leavetypes/rules/{id}",
     *     summary="更新假規",
     *     description="此 API 允許修改假規。",
     *     tags={"Leave Reset Rules"},
     *     security={{ "bearerAuth": {} }},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="假規 ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="leave_type_id", type="integer", example=1, description="對應的假別類型 ID"),
     *             @OA\Property(property="rule_type", type="string", example="monthly", description="假規類型，可選 `yearly` 或 `monthly`"),
     *             @OA\Property(property="rule_value", type="string", example="15", description="假規值，例如 '15' 代表每月 15 日重置")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="假規更新成功",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="更新成功"),
     *             @OA\Property(property="rule", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="leave_type_id", type="integer", example=1),
     *                 @OA\Property(property="rule_type", type="string", example="monthly"),
     *                 @OA\Property(property="rule_value", type="string", example="15")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="假規不存在",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="找不到該假規")
     *         )
     *     )
     * )
     */

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
    /**
     * @OA\Get(
     *     path="/api/leavetypes/rules",
     *     summary="取得所有假規",
     *     description="此 API 取得所有假規。",
     *     tags={"Leave Reset Rules"},
     *     security={{ "bearerAuth": {} }},
     *     @OA\Response(
     *         response=200,
     *         description="假規列表",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="leave_type_id", type="integer", example=1),
     *                 @OA\Property(property="rule_type", type="string", example="yearly"),
     *                 @OA\Property(property="rule_value", type="string", example="01-01"),
     *                 @OA\Property(property="leave_type", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Annual Leave"),
     *                     @OA\Property(property="description", type="string", example="年假")
     *                 )
     *             )
     *         )
     *     )
     * )
     */

    public function index(): JsonResponse
    {
        return response()->json(LeaveResetRule::with('leaveType')->get(), 200);
    }

    // 4. 刪除假規
    /**
     * @OA\Delete(
     *     path="/api/leavetypes/rules/{id}",
     *     summary="刪除假規",
     *     description="此 API 允許刪除假規。",
     *     tags={"Leave Reset Rules"},
     *     security={{ "bearerAuth": {} }},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="假規 ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="假規刪除成功",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="刪除成功")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="假規不存在",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="找不到該假規")
     *         )
     *     )
     * )
     */

    public function destroy($id): JsonResponse
    {
        $rule = LeaveResetRule::findOrFail($id);
        $rule->delete();

        return response()->json([
            'message' => '刪除成功',
        ], 200);
    }
}
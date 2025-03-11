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
    /**
     * @OA\Post(
     *     path="/api/leavetypes/add",
     *     summary="新增請假類型",
     *     description="此 API 允許新增請假類型，請確保名稱是唯一的。",
     *     tags={"Leave Types"},
     *     security={{ "bearerAuth": {} }},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Annual Leave", description="請假類型的英文名稱"),
     *             @OA\Property(property="description", type="string", example="年假", description="請假類型的中文名稱"),
     *             @OA\Property(property="total_hours", type="integer", example=40, description="假別總時數（可選）")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="假別新增成功",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="假別新增成功"),
     *             @OA\Property(property="leave_type", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Annual Leave"),
     *                 @OA\Property(property="description", type="string", example="年假"),
     *                 @OA\Property(property="total_hours", type="integer", example=40)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="請求資料驗證失敗",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="請假類型名稱已存在")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="未授權，請提供 JWT Token",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="未授權")
     *         )
     *     )
     * )
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
    /**
     * @OA\Put(
     *     path="/api/leavetypes/update/{id}",
     *     summary="修改請假類型",
     *     description="此 API 允許修改請假類型，請確保名稱是唯一的。",
     *     tags={"Leave Types"},
     *     security={{ "bearerAuth": {} }},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="請假類型 ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Sick Leave", description="請假類型的英文名稱"),
     *             @OA\Property(property="description", type="string", example="病假", description="請假類型的中文名稱"),
     *             @OA\Property(property="total_hours", type="integer", example=20, description="假別總時數（可選）")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="假別更新成功",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="假別更新成功"),
     *             @OA\Property(property="leave_type", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Sick Leave"),
     *                 @OA\Property(property="description", type="string", example="病假"),
     *                 @OA\Property(property="total_hours", type="integer", example=20)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="請假類型不存在",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="找不到該請假類型")
     *         )
     *     )
     * )
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
    /**
     * @OA\Delete(
     *     path="/api/leavetypes/{id}",
     *     summary="刪除請假類型",
     *     description="此 API 允許刪除請假類型。",
     *     tags={"Leave Types"},
     *     security={{ "bearerAuth": {} }},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="請假類型 ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="假別刪除成功",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="假別刪除成功")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="請假類型不存在",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="找不到該請假類型")
     *         )
     *     )
     * )
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
    /**
     * @OA\Get(
     *     path="/api/leavetypes/",
     *     summary="取得所有請假類型",
     *     description="此 API 取得所有請假類型，通常用於前端的請假類型選單。",
     *     tags={"Leave Types"},
     *     security={{ "bearerAuth": {} }},
     *     @OA\Response(
     *         response=200,
     *         description="請假類型列表",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Annual Leave"),
     *                 @OA\Property(property="description", type="string", example="年假"),
     *                 @OA\Property(property="total_hours", type="integer", example=40)
     *             )
     *         )
     *     )
     * )
     */

    public function index(): JsonResponse
    {
        return response()->json(LeaveType::all());
    }
}
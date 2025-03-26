<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Department;
use App\Models\Position;
use Illuminate\Http\JsonResponse;

class PositionController extends Controller
{
    // 取得所有職位 (包含部門名稱)
    /**
     * @OA\Get(
     *     path="/api/positions",
     *     summary="取得所有職位",
     *     description="返回所有職位列表，包含部門名稱",
     *     operationId="getAllPositions",
     *     tags={"Position"},
     *     security={{ "bearerAuth":{} }},
     *     @OA\Response(
     *         response=200,
     *         description="成功獲取職位列表",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="成功獲取所有職位"),
     *             @OA\Property(
     *                 property="positions",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Position")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="未授權",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="未授權的請求"))
     *     )
     * )
     */
    public function index(): JsonResponse
    {
        $positions = Position::with('department')->get();

        return response()->json([
            'message' => '成功獲取所有職位',
            'positions' => $positions
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    // 新增職位
    /**
     * @OA\Post(
     *     path="/api/positions",
     *     summary="新增職位",
     *     description="建立一個新的職位",
     *     operationId="createPosition",
     *     tags={"Position"},
     *     security={{ "bearerAuth":{} }},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="專案經理"),
     *             @OA\Property(property="department_id", type="integer", nullable=true, example=2)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="職位新增成功",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="職位新增成功"))
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="驗證失敗",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="職位名稱已存在"))
     *     )
     * )
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:positions,name',
            'department_id' => 'nullable|exists:departments,id'
        ]);

        $position = Position::create([
            'name' => $request->name,
            'department_id' => $request->department_id //不綁定部門，可以是null
        ]);

        return response()->json([
            'message' => '職位新增成功',
            'position' => $position
        ], 201);
    }

    // 根據部門篩選職位
    /**
     * @OA\Get(
     *     path="/api/positions/by/department/{id}",
     *     summary="根據部門 ID 篩選職位",
     *     description="根據部門 ID 篩選職位",
     *     operationId="getPositionsByDepartmentId",
     *     tags={"Position"},
     *     security={{ "bearerAuth":{} }},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="部門 ID",
     *         @OA\Schema(type="integer", example=6669)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="成功獲取職位",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="department", type="string", example="6669"),
     *             @OA\Property(
     *                 property="positions",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Position")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="找不到該部門",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="找不到該部門"))
     *     )
     * )
     */
    public function getByDepartmentId($id)
    {
        // 直接根據部門 ID 查找
        $department = Department::find($id);

        if (!$department) {
            return response()->json([
                'message' => '找不到該部門',
            ], 404);
        }

        // 取得該部門的所有職位
        $positions = Position::where('department_id', $department->id)->get();

        return response()->json([
            'department' => $department->name,
            'positions' => $positions
        ], 200);
    }

    // 為部門指派職位
    /**
     * @OA\Post(
     *     path="/api/positions/by/department/{name}",
     *     summary="為部門指派職位",
     *     description="將職位指派到特定部門",
     *     operationId="assignPositionToDepartment",
     *     tags={"Position"},
     *     security={{ "bearerAuth":{} }},
     *     @OA\Parameter(
     *         name="name",
     *         in="path",
     *         required=true,
     *         description="部門名稱",
     *         @OA\Schema(type="string", example="資訊部")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"id"},
     *             @OA\Property(property="id", type="integer", example=3)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="職位已指派到部門",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="職位已指派到部門"))
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="找不到該部門",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="找不到該部門"))
     *     )
     * )
     */
    public function assignPositionToDepartment(Request $request, $name)
    {
        $department = Department::where('name', $name)->first();

        if (!$department) {
            return response()->json([
                'message' => '找不到該部門',
            ], 404);
        }

        $validated = $request->validate([
            'id' => 'required|exists:positions,id'
        ]);

        // 取得職位
        $position = Position::find($validated['id']);

        // 更新職位的department_id
        $position->department_id = $department->id;
        $position->save();

        return response()->json([
            'message' => '職位已指派到部門'
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    // 更新職位
    /**
     * @OA\Patch(
     *     path="/api/positions/{id}",
     *     summary="更新職位",
     *     description="更新職位名稱或所屬部門",
     *     operationId="updatePosition",
     *     tags={"Position"},
     *     security={{ "bearerAuth":{} }},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="職位 ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="資深開發工程師"),
     *             @OA\Property(property="department_id", type="integer", nullable=true, example=2)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="職位更新成功",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="職位更新成功"))
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="找不到職位",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="找不到職位"))
     *     )
     * )
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'name' => "required|string|max:255|unique:positions,name,{$id}",
            'department_id' => 'nullable|exists:departments,id'
        ]);

        $position = Position::find($id);

        if (!$position) {
            return response()->json(['error' => '找不到職位'], 404);
        }

        $position->name = $request->name;
        $position->department_id = $request->department_id;
        $position->save();

        return response()->json(['message' => '職位更新成功'], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    // 刪除職位
    /**
     * @OA\Delete(
     *     path="/api/positions/{id}",
     *     summary="刪除職位",
     *     description="刪除指定職位",
     *     operationId="deletePosition",
     *     tags={"Position"},
     *     security={{ "bearerAuth":{} }},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="職位 ID",
     *         @OA\Schema(type="integer", example=3)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="職位刪除成功",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="職位刪除成功"))
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="找不到職位",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="找不到職位"))
     *     )
     * )
     */
    public function destroy(string $id)
    {
        $position = Position::find($id);

        if (!$position) {
            return response()->json(['error' => '找不到職位'], 404);
        }

        $position->delete();
        return response()->json(['message' => '職位刪除成功'], 200);
    }
}
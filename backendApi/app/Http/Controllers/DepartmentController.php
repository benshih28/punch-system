<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Department;

class DepartmentController extends Controller
{
    // 取得所有部門
    /**
     * @OA\Get(
     *     path="/api/departments",
     *     summary="取得所有部門",
     *     description="此 API 取得所有部門。",
     *     tags={"Departments"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="成功獲取所有部門",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="成功獲取所有部門"),
     *             @OA\Property(property="departments", type="array", 
     *                  @OA\Items(ref="#/components/schemas/Department")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="未授權，請提供有效的 Bearer Token",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="未授權的請求")
     *         )
     *     )
     * )
     */
    public function index()
    {
        $departments = Department::all(); // 取得所有部門

        return response()->json([
            'message' => '成功獲取所有部門',
            'departments' => $departments
        ], 200);
    }

    // 新增部門
    /**
     * @OA\Post(
     *     path="/api/departments",
     *     summary="新增部門",
     *     description="此 API 新增部門",
     *     tags={"Departments"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="人資部")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="部門已新增",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="部門已新增"),
     *             @OA\Property(property="department")
     *         )
     *     ),
     *     @OA\Response(response=422, description="請求驗證錯誤")
     * )
     */
    public function store(Request $request)
    {
        // 驗證請求資料
        $request->validate([
            'name' => 'required|string|unique:departments,name|max:255',
        ]);

        // 建立部門
        $department = Department::create([
            'name' => $request->name,
        ]);

        // 回傳JSON
        return response()->json([
            'message' => '部門已新增',
            'department' => $department,
        ], 201);
    }

    // 更新部門
    /**
     * @OA\Patch(
     *     path="/api/departments/{id}",
     *     summary="更新部門名稱",
     *     tags={"Departments"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="部門 ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="研發部")
     *         )
     *     ),
     *     @OA\Response(response=200, description="部門更新成功"),
     *     @OA\Response(response=404, description="找不到部門")
     * )
     */
    public function update(Request $request, $id)
    {
        $request->validate(['name' => 'required|string|unique:departments,name,' . $id]);

        $department = Department::findOrFail($id);
        $department->name = $request->name;
        $department->save();

        return response()->json(['message' => '部門更新成功'], 200); // 200 OK
    }

    // 刪除部門
    /**
     * @OA\Delete(
     *     path="/api/departments/{id}",
     *     summary="刪除部門",
     *     tags={"Departments"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="部門 ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="部門刪除成功"),
     *     @OA\Response(response=404, description="找不到部門")
     * )
     */
    public function destroy($id)
    {
        $department = Department::find($id);

        if (!$department) {
            return response()->json(['error' => '找不到部門'], 404); // 404 Not Found
        }

        $department->delete();
        return response()->json(['message' => '部門刪除成功'], 200); // 200 OK
    }
}
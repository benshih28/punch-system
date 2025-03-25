<?php

namespace App\Http\Controllers;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/roles",
     *     summary="建立新角色",
     *     description="HR 或 Admin 可建立新角色，並一次性指派權限。",
     *     operationId="createRole",
     *     tags={"Roles & Permissions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="角色名稱與權限",
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="manager", description="角色名稱"),
     *             @OA\Property(
     *                 property="permissions",
     *                 type="array",
     *                 @OA\Items(type="string", example="punch_in"),
     *                 description="權限名稱陣列（可選）"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="角色建立成功",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="角色建立成功"),
     *             @OA\Property(property="role", type="string", example="manager"),
     *             @OA\Property(
     *                 property="permissions",
     *                 type="array",
     *                 @OA\Items(type="string", example="punch_in"),
     *                 description="角色擁有的權限"
     *             )
     *         )
     *     ),
     *     @OA\Response(response=400, description="請求格式錯誤"),
     *     @OA\Response(response=403, description="沒有權限"),
     *     @OA\Response(response=422, description="驗證失敗"),
     *     @OA\Response(response=500, description="伺服器錯誤")
     * )
     */
    // ✅ 建立新角色並可選擇 `permissions`
    public function createRole(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:roles,name',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,name' // 確保權限存在
        ]);

        $role = Role::create(['name' => $request->name]);

        // ✅ 如果有 `permissions`，則同步更新
        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        return response()->json([
            'message' => '角色建立成功',
            'role' => $role->name,
            'permissions' => $role->permissions
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/roles",
     *     summary="取得所有角色",
     *     description="取得系統內的所有角色。",
     *     operationId="getAllRoles",
     *     tags={"角色管理"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="成功取得角色列表",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="admin"),
     *                 @OA\Property(property="guard_name", type="string", example="api"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-03-12 10:28:47"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2025-03-12 10:28:47")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="權限不足"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="未授權"
     *     )
     * )
     */
    // ✅ 取得所有角色
    public function getAllRoles()
    {
        return response()->json(Role::all());
    }

    // ✅ 1. 新增權限
    public function createPermission(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:permissions,name'
        ]);

        $permission = Permission::create(['name' => $request->name]);

        return response()->json([
            'message' => 'Permission created successfully',
            'permission' => $permission
        ], 201);
    }

    // ✅ 2. 取得所有權限
    public function getAllPermissions()
    {
        return response()->json(Permission::all());
    }

    // ✅ 3. 刪除權限
    public function deletePermission($id)
    {
        $permission = Permission::find($id);
        if (!$permission) {
            return response()->json(['error' => 'Permission not found'], 404);
        }

        $permission->delete();

        return response()->json(['message' => 'Permission deleted successfully']);
    }

    /**
     * @OA\Patch(
     *     path="/api/roles/{role}/permissions",
     *     summary="更新角色的權限和名稱",
     *     description="HR 或 Admin 可為角色指派新權限，並移除原本未包含的權限，同時可更新角色名稱。",
     *     operationId="assignPermission",
     *     tags={"Roles & Permissions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="role",
     *         in="path",
     *         required=true,
     *         description="角色名稱",
     *         @OA\Schema(type="string", example="manager")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="權限名稱列表和新角色名稱",
     *         @OA\JsonContent(
     *             required={"permissions"},
     *             @OA\Property(
     *                 property="permissions",
     *                 type="array",
     *                 @OA\Items(type="string", example="punch_in"),
     *                 description="新權限列表，舊權限未包含在此清單內的會自動被移除"
     *             ),
     *             @OA\Property(
     *                 property="name",
     *                 type="string",
     *                 example="supervisor",
     *                 description="新角色名稱（可選）"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="角色權限和名稱更新成功",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="角色權限和名稱已更新"),
     *             @OA\Property(property="role", type="string", example="supervisor"),
     *             @OA\Property(
     *                 property="permissions",
     *                 type="array",
     *                 @OA\Items(type="string", example="punch_in"),
     *                 description="角色擁有的最新權限"
     *             )
     *         )
     *     ),
     *     @OA\Response(response=400, description="請求格式錯誤"),
     *     @OA\Response(response=403, description="沒有權限"),
     *     @OA\Response(response=404, description="角色不存在"),
     *     @OA\Response(response=422, description="驗證失敗"),
     *     @OA\Response(response=500, description="伺服器錯誤")
     * )
     */
    public function assignPermission(Request $request, $roleName)
    {
        // 確保角色存在
        $role = Role::where('name', $roleName)->first();
        if (!$role) {
            return response()->json(['error' => 'Role not found'], 404);
        }

        // 驗證請求資料
        $validated = $request->validate([
            'permissions' => 'required|array', // 權限列表為必填
            'permissions.*' => 'string|exists:permissions,name', // 每個權限必須存在於 permissions 表中
            'name' => 'sometimes|string|unique:roles,name,' . $role->id, // 角色名稱為可選，但必須唯一
        ]);

        // 更新角色名稱（如果有提供）
        if ($request->has('name') && $request->name !== $role->name) {
            $role->name = $request->name;
            $role->save();
        }

        // 更新權限：移除舊權限，新增新權限
        $role->syncPermissions($validated['permissions']);

        // 返回更新後的角色資料
        return response()->json([
            'message' => '角色權限和名稱已更新',
            'role' => $role->name,
            'permissions' => $role->permissions->pluck('name'),
        ]);
    }
    /**
     * @OA\Get(
     *     path="/api/roles/{role}/permissions",
     *     summary="取得角色的所有權限",
     *     description="根據角色名稱取得該角色的所有權限。",
     *     operationId="getRolePermissions",
     *     tags={"角色管理"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="role",
     *         in="path",
     *         required=true,
     *         description="角色名稱",
     *         @OA\Schema(type="string", example="admin")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="成功取得角色的權限",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="role", type="string", example="admin"),
     *             @OA\Property(
     *                 property="permissions",
     *                 type="array",
     *                 @OA\Items(type="string", example="manage_roles")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="找不到角色"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="權限不足"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="未授權"
     *     )
     * )
     */
    public function getRolePermissions($roleName)
    {
        // ✅ 確保角色存在
        $role = Role::where('name', $roleName)->first();
        if (!$role) {
            return response()->json(['error' => 'Role not found'], 404);
        }

        // ✅ 取得角色的所有權限
        return response()->json([
            'role' => $role->name,
            'permissions' => $role->permissions->pluck('name')
        ]);
    }
}

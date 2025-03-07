<?php

namespace App\Http\Controllers;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleController extends Controller
{
    // ✅ 建立新角色並可選擇 `permissions`
    public function createRole(Request $request)
    {
        // 檢查使用者是否擁有 HR 或 Admin 角色
        if (!Auth::user() || !Auth::user()->hasRole(['HR', 'Admin'])) {
            return response()->json(['message' => 'Permission denied'], 403);
        }

        $request->validate([
            'name' => 'required|string|unique:roles,name',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,name' // 確保權限存在
        ]);

        $role = Role::create(['name' => $request->name]);

        // 如果有 `permissions`，則直接同步
        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        return response()->json([
            'message' => 'Role created successfully',
            'role' => $role->name,
            'permissions' => $role->permissions
        ], 201);
    }

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

    // ✅ 指派 `permissions` 給角色 (批量)
    public function assignPermission(Request $request, $roleName)
    {
        $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,name' // 確保權限名稱存在
        ]);

        $role = Role::where('name', $roleName)->first();
        if (!$role) {
            return response()->json(['error' => 'Role not found'], 404);
        }

        // ✅ 批量更新 `permissions`
        $role->syncPermissions($request->permissions);

        return response()->json([
            'message' => 'Permissions assigned successfully',
            'role' => $role->name,
            'permissions' => $role->permissions
        ]);
    }

    // ✅ 批量移除 `permissions` (刪除)
    public function revokePermission(Request $request, $roleName)
    {
        $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,name' // 確保權限名稱存在
        ]);

        $role = Role::where('name', $roleName)->first();
        if (!$role) {
            return response()->json(['error' => 'Role not found'], 404);
        }

        // ✅ 批量刪除 `permissions`
        foreach ($request->permissions as $permission) {
            $role->revokePermissionTo($permission);
        }

        return response()->json([
            'message' => 'Permissions revoked successfully',
            'role' => $role->name,
            'permissions' => $role->permissions
        ]);
    }


    public function getRolePermissions($roleName){
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

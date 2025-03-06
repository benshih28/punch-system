<?php

namespace App\Http\Controllers;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


class RoleController extends Controller
{
    // ✅ 建立新角色
    public function createRole(Request $request)
    {

        //檢查使用者是否擁有 HR 或 Admin 角色
        if (!Auth::user() || !Auth::user()->hasRole(['HR', 'Admin'])) {
            return response()->json(['message' => 'Permission denied'], 403);
        }

        $request->validate([
            'name' => 'required|string|unique:roles,name',
        ]);

        $role = Role::create(['name' => $request->name]);

        return response()->json(['message' => 'Role created successfully', 'role' => $role]);
    }

    // ✅ 取得所有角色
    public function getAllRoles()
    {
        $roles = Role::all();
        return response()->json($roles);
    }

    // ✅ 新增權限
    public function createPermission(Request $request)
    {
        //檢查使用者是否擁有 HR 或 Admin 角色
        if (!Auth::user() || !Auth::user()->hasRole(['HR', 'Admin'])) {
            return response()->json(['message' => 'Permission denied'], 403);
        }

        // 驗證請求參數
        $validated = $request->validate([
            'name' => 'required|string|unique:permissions,name',
            'guard_name' => 'nullable|string|'
        ]);

        $guardName = $validated['guard_name'] ?? 'api';

        // 先檢查權限是否已存在
        $existingPermission = Permission::where('name', $validated['name'])
            ->where('guard_name', $guardName)
            ->first();

        if ($existingPermission) {
            return response()->json([
                'message' => "權限 `{$validated['name']}` 已經存在於 `{$guardName}`",
                'data' => $existingPermission
            ], 409); // HTTP 409: Conflict
        }

        // 創建權限
        $permission = Permission::create(['name' => $validated['name']]);

        // 回傳 JSON
        return response()->json([
            'message' => "成功新增權限 `{$validated['name']}`",
            'data' => $permission
        ], 201);
    }

    // ✅ 指派權限給角色
    public function assignPermission(Request $request, $roleId)
    {
        $role = Role::findOrFail($roleId);
        $permissions = $request->input('permissions'); // 取得權限名稱陣列

        $role->givePermissionTo($permissions);

        return response()->json(['message' => 'Permissions assigned successfully']);
    }

    // ✅ 移除角色的權限
    public function revokePermission(Request $request, $roleId)
    {
        $role = Role::findOrFail($roleId);
        $permissions = $request->input('permissions');

        $role->revokePermissionTo($permissions);

        return response()->json(['message' => 'Permissions revoked successfully']);
    }
}

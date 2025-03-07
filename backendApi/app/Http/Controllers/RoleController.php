<?php

namespace App\Http\Controllers;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;


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

    // ✅ 新增權限(permissions資料表)
    public function createPermission(Request $request)
    {
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

    public function deletePermission($permissionId)
    {
        // 確保該權限存在
        $permission = Permission::findOrFail($permissionId);

        // 找出所有擁有該權限的角色
        $rolesWithPermission = $permission->roles;

        // 找出所有透過角色擁有該權限的使用者
        $usersWithPermission = User::whereHas('roles', function ($query) use ($permission) {
            $query->whereHas('permissions', function ($q) use ($permission) {
                $q->where('id', $permission->id);
            });
        })->get();

        // 從所有角色中移除該權限（影響 `role_has_permissions`）
        foreach ($rolesWithPermission as $role) {
            $role->revokePermissionTo($permission);
        }

        // 從所有使用者中移除該權限（影響 `model_has_permissions`）
        foreach ($usersWithPermission as $user) {
            $user->revokePermissionTo($permission);
        }

        // 刪除該權限（影響 `permissions` 資料表）
        $permission->delete();

        // 回傳 JSON，顯示哪些角色 & 使用者受到影響
        return response()->json([
            'message' => "成功刪除權限 `{$permission->name}`，並同步影響角色與使用者",
            'removed_from_roles' => $rolesWithPermission->pluck('name'),
            'affected_users' => $usersWithPermission->pluck('id'),
        ], 200);
    }


    // ✅ 指派權限給角色
    public function assignPermission(Request $request, $roleId)
    {
        $role = Role::findOrFail($roleId);
        $permissions = $request->input('permissions'); // 取得權限名稱陣列

        // 確保權限存在，且 guard_name 與角色匹配
        $roleGuard = $role->guard_name;
        $validPermissions = Permission::whereIn('name', $permissions)
            ->where('guard_name', $roleGuard)
            ->get();

        if ($validPermissions->isEmpty()) {
            return response()->json(['message' => 'No valid permissions found for this role guard'], 400);
        }

        $role->givePermissionTo($permissions);

        // 找出擁有該角色的所有使用者，並同步指派權限（影響 `model_has_permissions`）
        $usersWithRole = User::role($role->name)->get();
        foreach ($usersWithRole as $user) {
            $user->givePermissionTo($validPermissions);
        }

        // 回傳 JSON，顯示角色與使用者已獲得權限
        return response()->json([
            'message' => "成功指派權限給角色 `{$role->name}`，並同步給該角色的使用者",
            'role_permissions' => $role->permissions()->pluck('name'),
            'affected_users' => $usersWithRole->pluck('id')
        ]);
    }

    // ✅ 移除角色的權限
    public function revokePermission(Request $request, $roleId)
    {
        // 找到角色
        $role = Role::findOrFail($roleId);
        $permissions = $request->input('permissions');

        // 確保權限存在，且 guard_name 與角色匹配
        $roleGuard = $role->guard_name;
        $validPermissions = Permission::whereIn('name', $permissions)
            ->where('guard_name', $roleGuard)
            ->get();

        if ($validPermissions->isEmpty()) {
            return response()->json(['message' => 'No valid permissions found for this role guard'], 400);
        }

        // 從角色移除權限（影響 `role_has_permissions`）
        $role->revokePermissionTo($validPermissions);

        // 找出擁有該角色的所有使用者，並同步移除該權限（影響 `model_has_permissions`）
        $usersWithRole = User::role($role->name)->get();
        foreach ($usersWithRole as $user) {
            $user->revokePermissionTo($validPermissions);
        }

        // 回傳 JSON，顯示角色與使用者的權限已被移除
        return response()->json([
            'message' => "成功移除角色 `{$role->name}` 的權限，並同步影響該角色的使用者",
            'role_permissions' => $role->permissions()->pluck('name'), // 確認剩餘權限
            'affected_users' => $usersWithRole->pluck('id')
        ]);
    }
}

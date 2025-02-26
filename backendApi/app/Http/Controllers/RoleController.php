<?php

namespace App\Http\Controllers;
use Spatie\Permission\Models\Role;
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

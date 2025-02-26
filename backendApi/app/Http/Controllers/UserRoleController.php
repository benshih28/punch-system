<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;




class UserRoleController extends Controller
{
    // ✅ 指派角色給使用者
    public function assignRoleToUser(Request $request, $userId)
    {
        $user = User::findOrFail($userId);
        $roles = $request->input('roles'); // 角色名稱陣列

        $user->assignRole($roles);

        return response()->json(['message' => 'Roles assigned successfully']);
    }

    // ✅ 移除使用者的角色
    public function revokeRoleFromUser(Request $request, $userId)
    {
        $user = User::findOrFail($userId);
        $roles = $request->input('roles');

        $user->removeRole($roles);

        return response()->json(['message' => 'Roles revoked successfully']);
    }

    // ✅ 取得使用者的所有角色
    public function getUserRoles($userId)
    {
        $user = User::findOrFail($userId);
        return response()->json(['roles' => $user->getRoleNames()]);
    }

    // ✅ 取得使用者的所有權限
    public function getUserPermissions($userId)
    {
        $user = User::findOrFail($userId);
        return response()->json(['permissions' => $user->getPermissionNames()]);
    }
}

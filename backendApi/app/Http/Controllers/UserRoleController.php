<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;




class UserRoleController extends Controller
{
    // ✅ 指派角色給使用者
    // public function assignRoleToUser(Request $request, $userId)
    // {
    //     $user = User::findOrFail($userId);
    //     $roles = $request->input('roles'); // 角色名稱陣列

    //     $user->assignRole($roles);

    //     return response()->json(['message' => 'Roles assigned successfully']);
    // }


    /**
     * @OA\Get(
     *     path="/api/users/{userId}/roles",
     *     summary="取得使用者的角色",
     *     description="根據 `userId` 取得該使用者的角色列表。",
     *     operationId="getUserRoles",
     *     tags={"User Roles"},
     *     security={{"bearerAuth": {}}},  
     *
     *     @OA\Parameter(
     *         name="userId",
     *         in="path",
     *         required=true,
     *         description="使用者的 ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="成功取得使用者角色",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="roles",
     *                 type="array",
     *                 @OA\Items(type="string", example="admin")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="無權限查看角色"
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="找不到使用者"
     *     )
     * )
     */
    // ✅ 取得使用者的所有角色
    public function getUserRoles($userId)
    {
        $user = User::findOrFail($userId);
        return response()->json(['roles' => $user->getRoleNames()]);
    }
    /**
     * @OA\Get(
     *     path="/api/users/{userId}/permissions",
     *     summary="取得使用者的所有權限",
     *     description="根據 `userId` 取得該使用者的所有權限，包括直接擁有的權限和透過角色繼承的權限。",
     *     operationId="getUserPermissions",
     *     tags={"User Permissions"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="userId",
     *         in="path",
     *         required=true,
     *         description="使用者的 ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="成功取得使用者權限",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="user", type="string", example="王小明"),
     *             @OA\Property(
     *                 property="permissions",
     *                 type="array",
     *                 @OA\Items(type="string", example="view_attendance")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="無權限查看使用者權限"
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="找不到使用者"
     *     )
     * )
     */
    // ✅ 取得使用者的所有權限
    public function getUserPermissions($userId)
    {
        $user = User::findOrFail($userId);

        // 確保使用者直接擁有的 `permissions` + 繼承自 `roles` 的 `permissions`
        $permissions = $user->getAllPermissions()->pluck('name');

        return response()->json([
            'user' => $user->name,
            'permissions' => $permissions
        ]);
    }
}

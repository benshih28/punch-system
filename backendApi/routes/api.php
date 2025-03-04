<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

use App\Http\Controllers\PunchController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserRoleController;
use App\Http\Controllers\PunchCorrectionController;


// ✅ 公開 API（不需要登入）
Route::post('/register', [RegisteredUserController::class, 'store']);
Route::post('/login', [AuthenticatedSessionController::class, 'store']);

// ✅ 需要登入 (`auth:api`) 的 API
Route::middleware('auth:api')->group(function () {

    // 🟢 使用者相關
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy']);
    Route::get('/user', function (Request $request) {
        return response()->json($request->user());
    });

    // 🟢 打卡 API
    Route::prefix('/punch')->group(function () {
        Route::post('/in', [PunchController::class, 'punchIn']);
        Route::post('/out', [PunchController::class, 'punchOut']);
        Route::post('/correction', [PunchCorrectionController::class, 'store']); // 打卡補登請求
        Route::get('/correction', [PunchCorrectionController::class, 'getUserCorrections']); // 個人的補登打卡紀錄表單(可以選擇查看日期範圍)
    });

    // 🟢 查詢當前使用者打卡紀錄
    Route::get('/attendance/finalrecords', [PunchCorrectionController::class, 'getFinalAttendanceRecords']);



    // ✅ 只有 HR & Admin 才能存取的 API
    Route::middleware(['auth:api', 'can:isHRorAdmin'])->group(function () {

        // 角色管理 API
        Route::prefix('/roles')->group(function () {
            Route::post('/', [RoleController::class, 'createRole']);
            Route::get('/', [RoleController::class, 'getAllRoles']);
            Route::post('/{roleId}/assign/permissions', [RoleController::class, 'assignPermission']);
            Route::post('/{roleId}/revoke/permissions', [RoleController::class, 'revokePermission']);
        });


        // 使用者角色管理 API
        Route::prefix('/users')->group(function () {
            Route::post('/{userId}/assign/roles', [UserRoleController::class, 'assignRoleToUser']);
            Route::post('/{userId}/revoke/roles', [UserRoleController::class, 'revokeRoleFromUser']);
            Route::get('/{userId}/roles', [UserRoleController::class, 'getUserRoles']);
            Route::get('/{userId}/permissions', [UserRoleController::class, 'getUserPermissions']);
        });

        // 打卡補登審核通過或未通過
        Route::put('/punch/correction/{id}/approve', [PunchCorrectionController::class, 'approve']);
        Route::put('/punch/correction/{id}/reject', [PunchCorrectionController::class, 'reject']);

        // 人資看到所有申請資料(可以選擇查看日期範圍)
        Route::get('/corrections', [PunchCorrectionController::class, 'getAllCorrections']);

    });
});

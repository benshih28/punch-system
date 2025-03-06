<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

use App\Http\Controllers\PunchController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserRoleController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\LeaveTypeController;

use App\Http\Controllers\PunchCorrectionController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\PositionController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\Auth\ForgotPasswordController;


// ✅ 忘記密碼 API
Route::post('/forgot/password', [ForgotPasswordController::class, 'forgotPassword']);

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
    Route::get('/attendance/records', [PunchController::class, 'getAttendanceRecords']);
    Route::get('/attendance/finalrecords', [PunchCorrectionController::class, 'getFinalAttendanceRecords']);

    // 🟢 請假功能
    Route::middleware('auth:api')->prefix('leaves')->group(function () {
        // 1. 新增假別API
        Route::post('/type/add', [LeaveTypeController::class, 'addLeaveTypes']);
        // 2. 刪除假別API
        Route::post('/type/destroy/{id}', [LeaveTypeController::class, 'destroyLeaveTypes']);
        // 3. 修改假別API
        Route::post('/type/update/{id}', [LeaveTypeController::class, 'updateLeaveTypes']);
        // 4. 假別選單API (放下拉式選單內)
        Route::get('/leavetypes', [LeaveTypeController::class, 'getleaveTypes']);
        // 5. 狀態選單API (放下拉式選單內)
        Route::get('/leavestatus', [LeaveTypeController::class, 'getleaveStatus']);
        // 6. 取得特殊假別剩餘小時數
        Route::get('/remaininghours', [LeaveController::class, 'getRemainingLeaveHours']);


        // 1.請假申請API
        Route::post('/apply', [LeaveController::class, 'leaveApply']);
        // 2. 查詢請假紀錄API
        Route::get('/records', [LeaveController::class, 'leaveRecords']);
        // 3-1. 查詢單筆紀錄API
        Route::post('/{id}', [LeaveController::class, 'showLeave']);
        // 3-2. 修改請假申請API
        Route::put('/{id}', [LeaveController::class, 'updateLeave']);
        // 4. 刪除請假申請
        Route::delete('/{id}', [LeaveController::class, 'leaveApplyDelete']);
    });


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


        // 🔹 部門 API
        Route::prefix('/departments')->group(function () {
            Route::get('/', [DepartmentController::class, 'index']); // 取得所有部門
            Route::post('/', [DepartmentController::class, 'store']); // 新增部門
            Route::patch('/{id}', [DepartmentController::class, 'update']); // 更新部門
            Route::delete('/{id}', [DepartmentController::class, 'destroy']); // 刪除部門
        });

        // 🔹 職位 API
        Route::prefix('/positions')->group(function () {
            Route::get('/', [PositionController::class, 'index']); // 取得所有職位
            Route::get('/by/department/{name}', [PositionController::class, 'getByDepartment']); // 根據部門篩選職位
            Route::post('/by/department/{name}', [PositionController::class, 'assignPositionToDepartment']); // 為部門指派職位
            Route::post('/', [PositionController::class, 'store']); // 新增職位
            Route::patch('/{id}', [PositionController::class, 'update']); // 更新職位
            Route::delete('/{id}', [PositionController::class, 'destroy']); // 刪除職位
        });


        //人員管理 API
        Route::prefix('/employees')->group(function () {
            Route::get('/', [EmployeeController::class, 'index']); // 取得所有員工
            Route::post('/', [EmployeeController::class, 'store']); // 註冊員工
            Route::patch('/{id}/review', [EmployeeController::class, 'reviewEmployee']); // HR 審核
            Route::patch('/{id}/assign', [EmployeeController::class, 'assignDepartmentAndPosition']); // 分配職位 & 部門
            Route::delete('/{id}', [EmployeeController::class, 'destroy']); // 刪除員工
            Route::get('/{id}/manager', [EmployeeController::class, 'getEmployeeManager']); // 查詢主管
        });
    });


    Route::middleware(['auth:api', 'isManager'])->group(function () {
        Route::get('/my/employees', [EmployeeController::class, 'getMyEmployees']); // 主管查詢自己管理的員工
    });
});

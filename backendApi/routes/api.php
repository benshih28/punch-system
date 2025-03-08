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
use App\Http\Controllers\LeaveRuleController;

use App\Http\Controllers\PunchCorrectionController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\PositionController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\UserController;



// 公開 API（不需要登入）
// 註冊
Route::post('/register', [RegisteredUserController::class, 'store']);

// 忘記密碼 API
Route::post('/forgot/password', [ForgotPasswordController::class, 'forgotPassword']);
// 登入
Route::post('/login', [AuthenticatedSessionController::class, 'store']);


// ✅ 需要登入 (`auth:api`) 的 API
// 需要登入 (`auth:api`) 的 API
Route::middleware('auth:api')->group(function () {

    // 登出
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy']);
    // user 資料
    Route::get('/user', function (Request $request) {
        return response()->json($request->user());
    });

    // 🟢 更新使用者個人資料(大頭貼、更改新密碼)
    Route::post('/user/update/profile', [UserController::class, 'updateProfile']);

    // 🟢 大頭貼
    // Route::post('/upload/avatar', [FileController::class, 'uploadAvatar'])->middleware('auth');
    Route::get('/avatar', [FileController::class, 'getAvatar'])->middleware('auth');

    // 🟢 打卡 API
    Route::prefix('/punch')->group(function () {
        Route::post('/in', [PunchController::class, 'punchIn']);
        Route::post('/out', [PunchController::class, 'punchOut']);
        // 打卡補登請求
        Route::post('/correction', [PunchCorrectionController::class, 'store']); 
        // 個人的補登打卡紀錄表單(可以選擇查看日期範圍)
        Route::get('/correction', [PunchCorrectionController::class, 'getUserCorrections']); 
    });

    // 🟢 查詢當前使用者打卡紀錄
    Route::get('/attendance/records', [PunchController::class, 'getAttendanceRecords']);
    // 查詢當前使用者打卡紀錄
    Route::get('/attendance/finalrecords', [PunchCorrectionController::class, 'getFinalAttendanceRecords']);

    // 🟢 請假功能
    Route::middleware('auth:api')->prefix('leaves')->group(function () {
        // 1. 新增假別API
        Route::post('/types/add', [LeaveTypeController::class, 'addLeaveTypes']);
        // 2. 刪除假別API
        Route::delete('/types/{id}', [LeaveTypeController::class, 'destroyLeaveTypes']);
        // 3. 修改假別API
        Route::put('/types/update/{id}', [LeaveTypeController::class, 'updateLeaveTypes']);
        // 4. 假別選單API (放下拉式選單內)
        Route::get('/types', [LeaveTypeController::class, 'getleaveTypes']);
        // 5. 狀態選單API (放下拉式選單內)
        Route::get('/status', [LeaveTypeController::class, 'getleaveStatus']);

        // 1.請假申請API
        Route::post('/apply', [LeaveController::class, 'leaveApply']);
        // 2. 查詢個人請假紀錄API
        Route::get('/records', [LeaveController::class, 'personalLeaveList']);        
        // 3. 修改請假申請API
        Route::post('/records/{id}', [LeaveController::class, 'updateLeave']);
        // 4. 查詢「部門」請假紀錄
        Route::get('/department', [LeaveController::class, 'departmentLeaveRecords']);
        // 5. 查詢全公司請假紀錄（限HR）
        Route::get('/company', [LeaveController::class, 'companyLeaveRecords']);
        // 6. 刪除請假申請
        Route::delete('/{id}', [LeaveController::class, 'leaveApplyDelete']);
        
        // 1. 增加假別規則
        Route::post('/types/rules', [LeaveRuleController::class, 'addLeaveRule']);
        // 2. 修改假別規則
        Route::patch('/types/rules/{id}', [LeaveRuleController::class, 'updateLeaveRule']);
        // 3. 取得假別規則
        Route::get('/types/rules', [LeaveRuleController::class, 'getLeaveRules']);
        // 4. 刪除假別規則
        Route::delete('/types/rules/{id}', [LeaveRuleController::class, 'destroyLeaveRule']);
        // 5. 取得假別剩餘小時數
        Route::get('/remaininghours', [LeaveController::class, 'getRemainingLeaveHours']);
    });


    //  只有 HR & Admin 才能存取的 API
    Route::middleware(['auth:api', 'can:isHRorAdmin'])->group(function () {

        Route::prefix('/roles')->group(function () {
            // 建立角色
            Route::post('/', [RoleController::class, 'createRole']);
            // 取得所有角色
            Route::get('/', [RoleController::class, 'getAllRoles']);
            // 指派 `permissions` 給角色
            Route::patch('/{role}/permissions', [RoleController::class, 'assignPermission']);
            // 移除 `permissions`
            Route::delete('/{role}/permissions', [RoleController::class, 'revokePermission']);
            // 取得角色permissions
            Route::get('/{role}/permissions', [RoleController::class, 'getRolePermissions']); 
        });

    // 使用者角色管理 API (只處理「使用者」)
    Route::prefix('/users')->group(function () {
        // 指派 `roles` 給 `users`
        Route::post('/{userId}/assign/roles', [UserRoleController::class, 'assignRoleToUser']);
        // 移除 `roles`
        Route::delete('/{userId}/revoke/roles', [UserRoleController::class, 'revokeRoleFromUser']);
        // 取得 `users` 的 `roles`
        Route::get('/{userId}/roles', [UserRoleController::class, 'getUserRoles']);
        // 取得 `users` 的 `permissions`
        Route::get('/{userId}/permissions', [UserRoleController::class, 'getUserPermissions']);
    });


    // 權限管理 API
    Route::prefix('/permissions')->group(function () {
        // 新增權限
        Route::post('/', [RoleController::class, 'createPermission']);
        // 取得所有權限 
        Route::get('/', [RoleController::class, 'getAllPermissions']);
        // 刪除權限 
        Route::delete('/{id}', [RoleController::class, 'deletePermission']); 
    });


        // 打卡補登審核通過或未通過
        Route::put('/punch/correction/{id}/approve', [PunchCorrectionController::class, 'approve']);
        Route::put('/punch/correction/{id}/reject', [PunchCorrectionController::class, 'reject']);

        // 人資看到所有申請資料(可以選擇查看日期範圍)
        Route::get('/corrections', [PunchCorrectionController::class, 'getAllCorrections']);


        // 🔹 部門 API
        Route::prefix('/departments')->group(function () {
            // 取得所有部門
            Route::get('/', [DepartmentController::class, 'index']); 
            // 新增部門
            Route::post('/', [DepartmentController::class, 'store']); 
            // 更新部門
            Route::patch('/{id}', [DepartmentController::class, 'update']); 
            // 刪除部門
            Route::delete('/{id}', [DepartmentController::class, 'destroy']); 
        });

        // 🔹 職位 API
        Route::prefix('/positions')->group(function () {
            // 取得所有職位
            Route::get('/', [PositionController::class, 'index']); 
            // 根據部門篩選職位
            Route::get('/by/department/{name}', [PositionController::class, 'getByDepartment']); 
            // 為部門指派職位
            Route::post('/by/department/{name}', [PositionController::class, 'assignPositionToDepartment']); 
            // 新增職位
            Route::post('/', [PositionController::class, 'store']); 
            // 更新職位
            Route::patch('/{id}', [PositionController::class, 'update']); 
            // 刪除職位
            Route::delete('/{id}', [PositionController::class, 'destroy']); 
        });


        //人員管理 API
        Route::prefix('/employees')->group(function () {
            // 取得所有員工
            Route::get('/', [EmployeeController::class, 'index']); 
            // 註冊員工
            Route::post('/', [EmployeeController::class, 'store']); 
            // HR 審核
            Route::patch('/{id}/review', [EmployeeController::class, 'reviewEmployee']); 
            // 分配職位 & 部門
            Route::patch('/{id}/assign', [EmployeeController::class, 'assignDepartmentAndPosition']); 
            // 刪除員工
            Route::delete('/{id}', [EmployeeController::class, 'destroy']); 
            // 查詢主管
            Route::get('/{id}/manager', [EmployeeController::class, 'getEmployeeManager']); 
        });
    });


    Route::middleware(['auth:api', 'isManager'])->group(function () {
        // 主管查詢自己管理的員工
        Route::get('/my/employees', [EmployeeController::class, 'getMyEmployees']); 
    });
});

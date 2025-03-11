<?php
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

use App\Http\Controllers\PunchController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserRoleController;

use App\Http\Controllers\PunchCorrectionController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\PositionController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\LeaveTypeController;
use App\Http\Controllers\LeaveResetRuleController;
use App\Http\Controllers\LeaveController;




// 公開 API（不需要登入）
// 註冊
Route::post('/register', [RegisteredUserController::class, 'store']);

// 忘記密碼 API
Route::post('/forgot/password', [ForgotPasswordController::class, 'forgotPassword']);
// 登入
Route::post('/login', [AuthenticatedSessionController::class, 'store']);

// 需要登入 (`auth:api`) 的 API
Route::middleware('auth:api')->group(function () {

    // 登出
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy']);
    // user 資料
    Route::get('/user', function (Request $request) {
        return response()->json($request->user());
    });
    
    // 🟢 大頭貼
    Route::post('/upload/avatar', [FileController::class, 'uploadAvatar'])->middleware('auth');
    Route::get('/avatar', [FileController::class, 'getAvatar'])->middleware('auth');

    // 更新使用者個人資料(大頭貼、更改新密碼)
    Route::post('/user/update/profile', [UserController::class, 'updateProfile']);

    // 大頭貼
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

    // 查詢當前使用者打卡紀錄
    Route::get('/attendance/finalrecords', [PunchCorrectionController::class, 'getFinalAttendanceRecords']);



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

    // 假別功能API (需要加上Admin權限) 
    Route::middleware('auth:api')->prefix('leavetypes')->group(function () {
        // 1. 新增假別API
        Route::post('/add', [LeaveTypeController::class, 'store']);
       // 2. 修改假別API
        Route::put('/update/{id}', [LeaveTypeController::class, 'update']);
        // 3. 刪除假別API
        Route::delete('/{id}', [LeaveTypeController::class, 'destroy']);
        // 4. 假別選單API (放下拉式選單內)
        Route::get('/', [LeaveTypeController::class, 'index']);
    });

    // 假別規則API (需要加上Admin權限)
    Route::middleware('auth:api')->prefix('leavetypes')->group(function () { 
        // 1. 增加假規
        Route::post('/rules/add', [LeaveResetRuleController::class, 'store']);     
        // 2. 更新假規
        Route::patch('/rules/{id}', [LeaveResetRuleController::class, 'update']);    
        // 3. 查詢假規
        Route::get('/rules', [LeaveResetRuleController::class, 'index']);     
        // 4. 刪除假規
        Route::delete('/rules/{id}', [LeaveResetRuleController::class, 'destroy']);
    });

    // 請假功能
    Route::middleware('auth:api')->prefix('leave')->group(function () {
        // 1. 員工可以申請請假（需要 `request_leave` 權限）
        Route::post('/request', [LeaveController::class, 'requestLeave']);

        // 2. 員工、主管、HR 可以查詢自己的請假紀錄（需要 `view_leave_records` 權限）
        Route::get('/records', [LeaveController::class, 'viewMyLeaveRecords']);

        // 3. 員工或 HR 可以刪除請假資料（需要 `delete_leave` 權限）
        Route::delete('/{id}', [LeaveController::class, 'deleteLeave']);

        // 4. 員工或 HR 可以更新請假資料（需要 `update_leave` 權限）
        Route::post('/{id}', [LeaveController::class, 'updateLeave']);

        // 5. 主管或 HR 可以查看本部門請假紀錄（需要 `view_department_leave_records` 權限）
        Route::get('/department', [LeaveController::class, 'viewDepartmentLeaveRecords']);

        // 6. HR 可以查看全公司的請假紀錄（需要 `view_company_leave_records` 權限）
        Route::get('/company', [LeaveController::class, 'viewCompanyLeaveRecords']);

        // 7.HR 可以審核/駁回請假（需要 `approve_leave` 權限）
        Route::patch('/{id}/approve', [LeaveController::class, 'approveLeave'])->middleware('can:approve_leave');
        Route::patch('/{id}/reject', [LeaveController::class, 'rejectLeave'])->middleware('can:approve_leave');
        
        // 8.主管可以核准/駁回本部門請假單（需要 `approve_department_leave` 權限）
        Route::patch('/{id}/department/approve', [LeaveController::class, 'approveDepartmentLeave'])->middleware('can:approve_department_leave');
        Route::patch('/{id}/department/reject', [LeaveController::class, 'rejectDepartmentLeave'])->middleware('can:approve_department_leave');
    });

});

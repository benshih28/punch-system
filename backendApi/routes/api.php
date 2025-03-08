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


    //  限制 HR 審核通過的員工才能更換與獲取大頭貼
    Route::middleware(['auth', 'can:upload_avatar'])->post('/upload/avatar', [FileController::class, 'uploadAvatar']);
    Route::middleware(['auth', 'can:view_avatar'])->get('/avatar', [FileController::class, 'getAvatar']);


    // -------------------------------------打卡 API---------------------------------  
    Route::prefix('/punch')->group(function () {
        // 需要 `punch_in` 權限
        Route::post('/in', [PunchController::class, 'punchIn'])->middleware('can:punch_in');

        // 需要 `punch_out` 權限
        Route::post('/out', [PunchController::class, 'punchOut'])->middleware('can:punch_out');

        // 需要 `request_correction` 權限才能補登打卡
        Route::post('/correction', [PunchCorrectionController::class, 'store'])->middleware('can:request_correction');

        // 需要 `view_corrections` 權限才能查看自己的補登紀錄
        Route::get('/correction', [PunchCorrectionController::class, 'getUserCorrections'])->middleware('can:view_corrections');
    });

    // 查詢當前使用者的打卡紀錄 (需要 `view_attendance` 權限)
    Route::get('/attendance/finalrecords', [PunchCorrectionController::class, 'getFinalAttendanceRecords'])->middleware('can:view_attendance');

    // 打卡補登審核 (需要 `approve_correction` 權限)
    Route::put('/punch/correction/{id}/approve', [PunchCorrectionController::class, 'approve'])->middleware('can:approve_correction');
    Route::put('/punch/correction/{id}/reject', [PunchCorrectionController::class, 'reject'])->middleware('can:approve_correction');

    // 人資查看所有補打卡申請 (需要 `view_all_corrections` 權限)
    Route::get('/corrections', [PunchCorrectionController::class, 'getAllCorrections'])->middleware('can:view_all_corrections');





    // -------------------------------------角色與權限--------------------------------

    // 限制 `manage_roles` 權限才能管理角色
    Route::middleware('can:manage_roles')->prefix('/roles')->group(function () {
        // 建立角色
        Route::post('/', [RoleController::class, 'createRole']);
        // 取得所有角色
        Route::get('/', [RoleController::class, 'getAllRoles']);
        // 指派 `permissions` 給角色
        Route::patch('/{role}/permissions', [RoleController::class, 'assignPermission']);
        // 移除 `permissions`
        Route::delete('/{role}/permissions', [RoleController::class, 'revokePermission']);
        // 取得角色的 `permissions`
        Route::get('/{role}/permissions', [RoleController::class, 'getRolePermissions']);
    });

    // 限制 `assign_roles` 和 `revoke_roles` 權限才能管理使用者角色
    Route::middleware(['can:assign_roles', 'can:revoke_roles'])->prefix('/users')->group(function () {
        // 指派 `roles` 給 `users`
        Route::post('/{userId}/assign/roles', [UserRoleController::class, 'assignRoleToUser']);
        // 移除 `roles`
        Route::delete('/{userId}/revoke/roles', [UserRoleController::class, 'revokeRoleFromUser']);
    });

    // 限制 `view_roles` 權限才能查詢 `users` 的 `roles`
    Route::middleware('can:view_roles')->get('/users/{userId}/roles', [UserRoleController::class, 'getUserRoles']);

    // 限制 `view_permissions` 權限才能查詢 `users` 的 `permissions`
    Route::middleware('can:view_permissions')->get('/users/{userId}/permissions', [UserRoleController::class, 'getUserPermissions']);



    // 權限管理 API
    Route::prefix('/permissions')->group(function () {
        // 新增權限
        Route::post('/', [RoleController::class, 'createPermission']);
        // 取得所有權限 
        Route::get('/', [RoleController::class, 'getAllPermissions']);
        // 刪除權限 
        Route::delete('/{id}', [RoleController::class, 'deletePermission']);
    });



    // -------------------------------------部門職位------------------------------

    // 部門 API（需要 `manage_departments` 權限）
    Route::middleware('can:manage_departments')->prefix('/departments')->group(function () {
        // 取得所有部門
        Route::get('/', [DepartmentController::class, 'index']);
        // 新增部門
        Route::post('/', [DepartmentController::class, 'store']);
        // 更新部門
        Route::patch('/{id}', [DepartmentController::class, 'update']);
        // 刪除部門
        Route::delete('/{id}', [DepartmentController::class, 'destroy']);
    });

    // 職位 API（需要 `manage_positions` 權限）
    Route::middleware('can:manage_positions')->prefix('/positions')->group(function () {
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


    // -------------------------------------人員管理 API--------------------------------
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

    Route::middleware(['auth:api', 'isManager'])->group(function () {
        // 主管查詢自己管理的員工
        Route::get('/my/employees', [EmployeeController::class, 'getMyEmployees']);
    });


    // -------------------------------------請假功能------------------------------
    // 請假 API
    Route::prefix('/leave')->group(function () {
        // 員工可以申請請假（需要 `request_leave` 權限）
        Route::post('/request', [LeaveController::class, 'requestLeave'])->middleware('can:request_leave');

        // 主管或 HR 可以審核請假（需要 `approve_leave` 權限）
        Route::patch('/{id}/approve', [LeaveController::class, 'approveLeave'])->middleware('can:approve_leave');
        Route::patch('/{id}/reject', [LeaveController::class, 'rejectLeave'])->middleware('can:approve_leave');

        // 員工、主管、HR 可以查詢自己的請假紀錄（需要 `view_leave_records` 權限）
        Route::get('/records', [LeaveController::class, 'viewMyLeaveRecords'])->middleware('can:view_leave_records');

        // 員工或 HR 可以刪除請假資料（需要 `delete_leave` 權限）
        Route::delete('/{id}', [LeaveController::class, 'deleteLeave'])->middleware('can:delete_leave');

        // 主管或 HR 可以查看本部門請假紀錄（需要 `view_department_leave_records` 權限）
        Route::get('/department', [LeaveController::class, 'viewDepartmentLeaveRecords'])->middleware('can:view_department_leave_records');

        // HR 可以查看全公司的請假紀錄（需要 `view_company_leave_records` 權限）
        Route::get('/company', [LeaveController::class, 'viewCompanyLeaveRecords'])->middleware('can:view_company_leave_records');

        // 主管或 HR 可以核准/駁回本部門請假單（需要 `approve_department_leave` 權限）
        Route::patch('/{id}/department/approve', [LeaveController::class, 'approveDepartmentLeave'])->middleware('can:approve_department_leave');
        Route::patch('/{id}/department/reject', [LeaveController::class, 'rejectDepartmentLeave'])->middleware('can:approve_department_leave');
    });
});

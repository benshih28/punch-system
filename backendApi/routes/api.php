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


    // 需要通過審核才能使用的 API
    Route::middleware('approved')->group(function () {

        // 更新使用者個人資料(大頭貼、更改新密碼)
        Route::post('/user/update/profile', [UserController::class, 'updateProfile']);

        // 大頭貼
        // Route::post('/upload/avatar', [FileController::class, 'uploadAvatar'])->middleware('auth');
        Route::get('/avatar', [FileController::class, 'getAvatar']);

        // 🟢 打卡 API
        Route::prefix('/punch')->group(function () {
            // (需要 `punch_in` 權限)
            Route::post('/in', [PunchController::class, 'punchIn'])->middleware('can:punch_in');
            // (需要 `punch_out` 權限)
            Route::post('/out', [PunchController::class, 'punchOut'])->middleware('can:punch_out');
            // 打卡補登請求 (需要 `request_correction` 權限)
            Route::post('/correction', [PunchCorrectionController::class, 'store'])->middleware('can:request_correction');
            // 個人的補登打卡紀錄表單(可以選擇查看日期範圍) (需要 `view_corrections` 權限)
            Route::get('/correction', [PunchCorrectionController::class, 'getUserCorrections'])->middleware('can:view_corrections');
        });

        // 查詢當前使用者打卡紀錄 （需要 `view_attendance` 權限）
        Route::get('/attendance/record', [PunchCorrectionController::class, 'ggetAllAttendanceRecords'])->middleware('can:view_attendance');



        // 角色管理 API （需要 `manage_roles` 權限）
        Route::middleware('can:manage_roles')->prefix('/roles')->group(function () {
            // 建立角色
            Route::post('/', [RoleController::class, 'createRole']);
            // 取得所有角色
            Route::get('/', [RoleController::class, 'getAllRoles']);
            // 指派或更新 `permissions` 給角色（移除舊的，指派新的）
            Route::patch('/{role}/permissions', [RoleController::class, 'assignPermission']);
            // 取得角色permissions
            Route::get('/{role}/permissions', [RoleController::class, 'getRolePermissions']);
        });

        // 使用者角色管理 API (只處理「使用者」)
        Route::prefix('/users')->group(function () {

            // (admin)指派 `roles` 給 `users`
            //Route::post('/{userId}/assign/roles', [UserRoleController::class, 'assignRoleToUser']);

            // 取得 `users` 的 `roles` (需要 `view_roles` 權限)
            Route::get('/{userId}/roles', [UserRoleController::class, 'getUserRoles'])->middleware('can:view_roles');
            // 取得 `users` 的 `permissions` (需要 `view_permissions` 權限)
            Route::get('/{userId}/permissions', [UserRoleController::class, 'getUserPermissions'])->middleware('can:view_permissions');
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


        // 打卡補登審核通過或未通過 (需要 `approve_correction` 權限)
        Route::put('/punch/correction/{id}/approve', [PunchCorrectionController::class, 'approve'])->middleware('can:approve_correction');
        Route::put('/punch/correction/{id}/reject', [PunchCorrectionController::class, 'reject'])->middleware('can:approve_correction');

        // 人資看到所有補登打卡申請資料(可以選擇查看日期範圍) (需要 `view_all_corrections` 權限)
        Route::get('/corrections', [PunchCorrectionController::class, 'getAllCorrections'])->middleware('can:view_all_corrections');
        // 人資看到所有人的打卡紀錄
        Route::get('/attendancerecords', [PunchCorrectionController::class, 'getAllAttendanceRecords']);


        // 部門 API（需要 `manage_departments` 權限）
        Route::prefix('/departments')->middleware('can:manage_departments')->group(function () {
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
        Route::prefix('/positions')->middleware('can:manage_positions')->group(function () {
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


        //人員管理 API（需要 `manage_employees` 權限）
        Route::prefix('/employees')->middleware('can:manage_employees')->group(function () {
            // 取得所有員工
            Route::get('/', [EmployeeController::class, 'index']);
            // 註冊員工（需要 `register_employee` 權限）
            Route::post('/', [EmployeeController::class, 'store'])->middleware('can:register_employee');
            // HR 審核員工註冊（需要 `review_employee` 權限）
            Route::patch('/{id}/review', [EmployeeController::class, 'reviewEmployee'])->middleware('can:review_employee');
            //分配&變更部門、職位、主管、角色（需要 `assign_employee_details` 權限）
            Route::patch('/{id}/assign', [EmployeeController::class, 'assignEmployeeDetails'])->middleware('can:assign_employee_details');

            // 刪除員工（需要 `delete_employee` 權限）
            Route::delete('/{id}', [EmployeeController::class, 'destroy'])->middleware('can:delete_employee');
            // // 查詢主管
            // Route::get('/{id}/manager', [EmployeeController::class, 'getEmployeeManager']);
        });


        // 主管查詢自己管理的員工（需要 `view_manager` 權限）
        Route::get('/my/employees', [EmployeeController::class, 'getMyEmployees'])->middleware('can:view_manager');


        //【請假管理 API】

        // 取得請假餘額
        Route::get('/leave/balances', [LeaveController::class, 'getLeaveBalances'])
            ->can('view_leave_records');

        // 申請請假 (含附件)
        Route::post('/leave/request', [LeaveController::class, 'requestLeave'])
            ->can('request_leave');

        // 修改請假申請
        Route::put('/leave/update/{id}', [LeaveController::class, 'updateLeave'])
            ->can('update_leave');

        // 取消請假
        Route::delete('/leave/cancel/{id}', [LeaveController::class, 'cancelLeave'])
            ->can('delete_leave');

        //【請假審核 API】

        // 主管審核請假
        Route::post('/leave/approve/manager/{id}', [LeaveController::class, 'approveLeaveByManager'])
            ->can('approve_department_leave');

        // HR 審核請假
        Route::post('/leave/approve/hr/{id}', [LeaveController::class, 'approveLeaveByHR'])
            ->can('approve_leave');

        // HR 修正請假紀錄
        Route::put('/leave/correct/{id}', [LeaveController::class, 'correctLeave'])
            ->can('approve_leave');

        //【請假查詢 API】

        // 員工個人請假紀錄查詢
        Route::get('/leave/personal-records', [LeaveController::class, 'getPersonalLeaveRecords'])
            ->can('view_leave_records');

        // 假單審核查詢 (主管 & HR)
        Route::get('/leave/approvals', [LeaveController::class, 'getLeaveApplicationsForApproval'])
            ->can('view_department_leave_records');
    });
});

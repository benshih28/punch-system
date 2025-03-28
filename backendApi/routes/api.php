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
use App\Http\Controllers\Auth\SocialLoginController;

use App\Http\Controllers\QuestionFeedbackController;
use App\Models\Notification;

// 公開 API（不需要登入）
// 問題反饋
Route::post('/send-email', [QuestionFeedbackController::class, 'sendFeedback']);
// 註冊
Route::post('/register', [RegisteredUserController::class, 'store']);

// 忘記密碼 API
Route::post('/forgot/password', [ForgotPasswordController::class, 'forgotPassword']);
// 登入
Route::post('/login', [AuthenticatedSessionController::class, 'store']);
Route::post('/login/google', [SocialLoginController::class, 'handleGoogleLogin']);


// 需要登入 (`auth:api`) 的 API
Route::middleware('auth:api')->group(function () {

    // ✅ 取得使用者通知（排除封存）
    Route::get('/notifications', function () {
        return Notification::where('user_id', auth()->id())
            ->where('archived', false) // ✅ 排除已封存
            ->orderBy('created_at', 'desc')
            ->get();
    });

    // ✅ 單筆標記為已讀
    Route::post('/notifications/{id}/read', function ($id) {
        $notif = Notification::where('user_id', auth()->id())->findOrFail($id);
        $notif->update(['read' => true]);
        return response()->json(['status' => 'read']);
    });

    // ✅ 封存通知（多筆）
    Route::post('/notifications/archive', function (Request $request) {
        $ids = $request->input('ids', []);
        Notification::where('user_id', auth()->id())
            ->whereIn('id', $ids)
            ->update(['archived' => true]);

        return response()->json(['status' => 'archived']);
    });

    // 登出
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy']);


    // user 資料
    Route::get('/user/details', [UserController::class, 'getUserDetails']);
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
            // 刪除補登打卡請求
            Route::delete('/correction/{id}', [PunchCorrectionController::class, 'destroy'])->middleware('can:request_correction');
            // 更新補登打卡請求
            Route::patch('/correction/{id}', [PunchCorrectionController::class, 'update'])->middleware('can:request_correction');
            // 個人的補登打卡紀錄表單(可以選擇查看日期範圍) (需要 `view_corrections` 權限)
            Route::get('/correction', [PunchCorrectionController::class, 'getUserCorrections'])->middleware('can:view_corrections');
        });

        // 查詢當前使用者打卡紀錄 （需要 `view_attendance` 權限）
        Route::get('/attendance/record', [PunchCorrectionController::class, 'getAllAttendanceRecords'])->middleware('can:view_attendance');



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
            Route::get('/by/department/{id}', [PositionController::class, 'getByDepartmentId']);
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
            // 取得已審核的員工
            Route::get('/approved', [EmployeeController::class, 'getApprovedEmployees']);
            // 刪除員工（需要 `delete_employee` 權限）
            Route::delete('/{id}', [EmployeeController::class, 'destroy'])->middleware('can:delete_employee');
            // // 查詢主管
            // Route::get('/{id}/manager', [EmployeeController::class, 'getEmployeeManager']);
        });


        // 主管查詢自己管理的員工（需要 `view_manager` 權限）
        Route::get('/my/employees', [EmployeeController::class, 'getMyEmployees'])->middleware('can:view_manager');

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
            // 5. 取得假別剩餘小時數
            Route::get('/hours/{leaveTypeId}', [LeaveController::class, 'getRemainingLeaveHours']);
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
            Route::post('/request', [LeaveController::class, 'requestLeave'])->middleware('can:request_leave');

            // 2. 員工、主管、HR 可以查詢自己的請假紀錄（需要 `view_leave_records` 權限）
            Route::get('/my-records', [LeaveController::class, 'viewMyLeaveRecords'])->middleware('can:view_leave_records');

            // 3. 員工或 HR 可以刪除請假資料（需要 `delete_leave` 權限）
            Route::delete('/{id}', [LeaveController::class, 'deleteLeave'])->middleware('can:delete_leave');

            // 4. 員工或 HR 可以更新請假資料（需要 `update_leave` 權限）
            Route::post('/update/{id}', [LeaveController::class, 'updateLeave'])->middleware('can:update_leave');

            // 5. 主管或 HR 可以查看本部門請假紀錄（需要 `view_department_leave_records` 權限）
            Route::get('/department', [LeaveController::class, 'viewDepartmentLeaveRecords'])->middleware('can:view_department_leave_records');

            // 6. HR 可以查看全公司的請假紀錄（需要 `view_company_leave_records` 權限）
            Route::get('/company', [LeaveController::class, 'viewCompanyLeaveRecords'])->middleware('can:view_company_leave_records');


            // 7.HR 可以審核/駁回請假（需要 `approve_leave` 權限）
            Route::patch('/{id}/approve', [LeaveController::class, 'approveLeave'])->middleware('can:approve_leave');
            Route::patch('/{id}/reject', [LeaveController::class, 'rejectLeave'])->middleware('can:approve_leave');

            // 8.主管可以核准/駁回本部門請假單（需要 `approve_department_leave` 權限）
            Route::patch('/{id}/department/approve', [LeaveController::class, 'approveDepartmentLeave'])->middleware('can:approve_department_leave');
            Route::patch('/{id}/department/reject', [LeaveController::class, 'rejectDepartmentLeave'])->middleware('can:approve_department_leave');
        });
    });
});

<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

use App\Http\Controllers\PunchController;



Route::post('/register', [RegisteredUserController::class, 'store']);
Route::post('/login', [AuthenticatedSessionController::class, 'store']);
Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->middleware('auth:api');

// 取得當前使用者
Route::middleware('auth:api')->get('/user', function (Request $request) {
    return response()->json($request->user());
});


/**
 *   上班打卡 API
 */

 Route::post('/punch/in', [PunchController::class, 'punchIn'])->middleware('auth:api');

 /**
  *   下班打卡 API
  */
 Route::post('/punch/out', [PunchController::class, 'punchOut'])->middleware('auth:api');
 
 /**
  *   查詢當前使用者打卡紀錄 API
  */
 
 Route::get('/attendance/records', [PunchController::class, 'getAttendanceRecords'])->middleware('auth:api');
<?php

namespace App\Http\Controllers;

use App\Helpers\LeaveHelper;
use Illuminate\Http\Request;

class LeaveTypeController extends Controller
{
    // 假別API
    // 從app/Helpers/LeaveHelper.php 提取資料，
    // 因LeaveHelper.php已有定義中文，所以前端呼叫這支API時跑出的是中文，不須再翻譯
    public function getLeaveTypes()
    {
        return response()->json(LeaveHelper::allLeaveTypes());
    }

    // 審核狀態API
    public function getLeaveStatus()
    {
        return response()->json(LeaveHelper::allLeaveStatuses());
    }
}

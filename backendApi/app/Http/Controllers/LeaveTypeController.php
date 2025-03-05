<?php

namespace App\Http\Controllers;

use App\Helpers\LeaveHelper;
use Illuminate\Http\Request;
use App\Models\LeaveType;

class LeaveTypeController extends Controller
{
    /**
     * 新增假別 (新增自訂假別或填充預設假別)
     */
    public function leaveTypesAdd(Request $request)
    {
        // 預設假別清單
        $defaultLeaveTypes = LeaveHelper::allLeaveTypes();

        // 先填充預設假別（只會新增一次）
        foreach ($defaultLeaveTypes as $defaultType) {
            LeaveType::firstOrCreate([
                'name' => $defaultType['key'],  // 使用預設的key
                'description' => $defaultType['label'],  // 使用預設的label
                'is_system_default' => true,  // 設定為預設假別
            ]);
        }

        // 新增自訂假別
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:leave_types,name', // 假別名稱唯一
            'description' => 'required|string|max:255', // 假別名稱
        ]);

        $leaveType = LeaveType::create([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'is_system_default' => false, // 這邊設定為`false`，表示它是自訂假別
        ]);

        return response()->json($leaveType, 201); // 返回新增的假別資料
    }
}

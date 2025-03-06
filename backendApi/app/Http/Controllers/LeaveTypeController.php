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
        // 檢查預設假別是否已經填充過
        if (!LeaveType::where('is_system_default', true)->exists()) {
            // 預設假別清單
            $defaultLeaveTypes = LeaveHelper::allLeaveTypes();

            // 1. 系統自動填入預設假別（只會新增一次）
            foreach ($defaultLeaveTypes as $defaultType) {
                LeaveType::firstOrCreate([
                    'name' => $defaultType['key'],          // 使用預設的key
                    'description' => $defaultType['label'], // 使用預設的label
                    'is_system_default' => true,            // 設定為預設假別
                ]);
            }
        }

        // 2. 新增自訂假別
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:leave_types,name', // 假別名稱唯一
            'description' => 'required|string|max:255', // 假別名稱中文
        ]);

        $leaveType = LeaveType::create([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'is_system_default' => false, // 這邊設定為`false`，表示它是自訂假別
        ]);

        return response()->json($leaveType, 201); // 返回新增的假別資料
    }

    // 3. 刪除假別
    public function leaveTypesDestroy($id)
    {
        $leaveType = LeaveType::find($id);

        // 如果該假別不存在，返回 404 錯誤
        if (!$leaveType) {
            return response()->json(['message' => '假別未找到'], 404);
        }

        // 如果是預設假別，則不能刪除
        if ($leaveType->is_system_default) {
            return response()->json(['message' => '預設假別無法刪除'], 403);
        }

        // 自訂假別可刪除
        $leaveType->delete();
        return response()->json(['message' => '假別刪除成功'], 200);
    }
}

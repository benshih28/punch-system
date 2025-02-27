<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Department;
use App\Models\Position;
use Illuminate\Http\JsonResponse;

class PositionController extends Controller
{
    // 取得所有職位 (包含部門名稱)
    public function index(): JsonResponse
    {
        $positions = Position::with('department')->get();

        return response()->json([
            'message' => '成功獲取所有職位',
            'positions' => $positions
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    // 在指定部門新增職位
    public function store(Request $request, string $name): JsonResponse
    {
        // 先取得部門
        $department = Department::where('name', $name)->first();

        if (!$department) {
            return response()->json([
                'message' => '找不到該部門',
            ], 404);
        }

        // 驗證請求資料
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        // 新增職位
        $position = Position::create([
            'department_id' => $department->id,
            'name' => $validated['name'],
        ]);

        return response()->json([
            'message' => '職位新增成功！',
            'position' => $position
        ], 201);
    }

    // 取得特定部門的所有職位
    public function getPositionsByDepartment(string $name): JsonResponse
    {
        // 先取得部門
        $department = Department::where('name', $name)->first();

        if (!$department) {
            return response()->json([
                'message' => '找不到該部門',
            ], 404);
        }

        // 取得該部門的所有職位
        $positions = $department->positions()->get();

        return response()->json([
            'message' => '成功獲取部門的所有職位',
            'department' => $department->name,
            'positions' => $positions
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
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
    // 新增職位
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:positions,name',
            'department_id' => 'nullable|exists:departments,id'
        ]);

        $position = Position::create([
            'name' => $request->name,
            'department_id' => $request->department_id //不綁定部門，可以是null
        ]);

        return response()->json([
            'message' => '職位新增成功',
            'position' => $position
        ], 201);
    }

    // 根據部門篩選職位
    public function getByDepartment($name)
    {
        // 先找到部門
        $department = Department::where('name', $name)->first();

        if (!$department) {
            return response()->json([
                'message' => '找不到該部門',
            ], 404);
        }

        // 取得該部門的所有職位
        $positions = Position::where('department_id', $department->id)->get();

        return response()->json([
            'department' => $department->name,
            'positions' => $positions
        ], 200);
    }

    // 為部門指派職位
    public function assignPositionToDepartment(Request $request, $name)
    {
        $department = Department::where('name', $name)->first();

        if (!$department) {
            return response()->json([
                'message' => '找不到該部門',
            ], 404);
        }

        $validated = $request->validate([
            'id' => 'required|exists:positions,id'
        ]);

        // 取得職位
        $position = Position::find($validated['id']);

        // 更新職位的department_id
        $position->department_id = $department->id;
        $position->save();

        return response()->json([
            'message' => '職位已指派到部門'
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:positions,name',
            'department_id' => 'nullable|exists:departments,id'
        ]);

        $position = Position::find($id);

        if (!$position) {
            return response()->json(['error' => '找不到職位'], 404);
        }

        $position->name = $request->name;
        $position->department_id = $request->department_id;
        $position->save();

        return response()->json(['message' => '職位更新成功'], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $position = Position::find($id);

        if (!$position) {
            return response()->json(['error' => '找不到職位'], 404);
        }

        $position->delete();
        return response()->json(['message' => '職位刪除成功'], 200);
    }
}
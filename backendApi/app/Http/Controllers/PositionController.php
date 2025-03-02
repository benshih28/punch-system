<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Department;
use App\Models\Position;


class PositionController extends Controller
{
    // 取得所有職位
    public function index()
    {
        return response()->json(Position::with('department')->get(), 200);
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

    // 新增職位
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:positions, name',
        ]);

        $position = Position::create([
            'department_id' => null, //不綁定部門
            'name' => $request->name
        ]);

        return response()->json([
            'message' => '職位新增成功',
            'position' => $position
        ], 201);
    }

    // 更新職位
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string',
            'department_id' => 'required|exists:departments,id'
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

    // 刪除職位
    public function destroy($id)
    {
        $position = Position::find($id);

        if (!$position) {
            return response()->json(['error' => '找不到職位'], 404);
        }

        $position->delete();
        return response()->json(['message' => '職位刪除成功'], 200);
    }
}
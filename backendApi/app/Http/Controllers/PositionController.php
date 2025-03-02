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


    // 取得特定部門的職位
    public function getByDepartment($departmentId)
    {
        $positions = Position::where('department_id', $departmentId)->get();

        if ($positions->isEmpty()) {
            return response()->json(['message' => '該部門沒有職位'], 404);
        }

        return response()->json($positions, 200);
    }

    // 新增職位
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'department_id' => 'required|exists:departments,id'
        ]);

        Position::create([
            'department_id' => $request->department_id,
            'name' => $request->name
        ]);

        return response()->json(['message' => '職位新增成功'], 201);
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
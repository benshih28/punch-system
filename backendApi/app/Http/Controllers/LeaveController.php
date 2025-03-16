<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LeaveBalance;
use App\Models\Leave;
use App\Models\LeaveType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\File;

class LeaveController extends Controller
{

    /**
     * @OA\Get(
     *     path="/api/leave/balances",
     *     summary="取得員工請假餘額",
     *     description="回傳員工的請假餘額，並扣除待審核請假時數",
     *     tags={"請假管理"},
     *     security={{ "bearerAuth":{} }},
     *     @OA\Response(
     *         response=200,
     *         description="成功回傳請假餘額",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="employee_id", type="integer", example=3),
     *             @OA\Property(
     *                 property="leave_balances",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="leave_type", type="string", example="特休假"),
     *                     @OA\Property(property="remaining_hours", type="integer", example=18),
     *                     @OA\Property(property="pending_hours", type="integer", example=6)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="找不到員工資料"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="未授權，請先登入"
     *     )
     * )
     */
    public function getLeaveBalances()
    {
        $employee = auth()->user()->employee;

        if (!$employee) {
            return response()->json(['error' => '找不到員工資料'], 404);
        }

        // 取得所有請假餘額
        $leaveBalances = LeaveBalance::where('employee_id', $employee->id)->get();

        // 取得待審核請假時數 (確保 `manager_status` 或 `hr_status` 為 `pending` 才計算)
        $pendingLeaves = Leave::where('employee_id', $employee->id)
            ->whereIn('manager_status', ['pending'])
            ->whereIn('hr_status', ['pending'])
            ->groupBy('leave_type_id')
            ->selectRaw('leave_type_id, SUM(hours) as pending_hours')
            ->get()
            ->pluck('pending_hours', 'leave_type_id'); // 取得 { leave_type_id => pending_hours } 陣列

        // 調整顯示餘額 (不改變 DB，只是回傳給前端)
        $formattedBalances = $leaveBalances->map(function ($balance) use ($pendingLeaves) {
            $pendingHours = $pendingLeaves[$balance->leave_type_id] ?? 0;
            return [
                'leave_type' => $balance->leaveType->name,
                'remaining_hours' => max(0, $balance->remaining_hours - $pendingHours),
                'pending_hours' => $pendingHours
            ];
        });

        return response()->json([
            'employee_id' => $employee->id,
            'leave_balances' => $formattedBalances
        ]);
    }


    /**
     * @OA\Post(
     *     path="/leave/request",
     *     summary="申請請假",
     *     description="員工提交請假申請，MySQL 預存程序自動計算請假時數、檢查時間衝突及性別限制。",
     *     tags={"Leave"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"leave_type_id", "start_date", "end_date", "reason"},
     *             @OA\Property(property="leave_type_id", type="integer", example=1, description="請假類型 ID"),
     *             @OA\Property(property="start_date", type="string", format="date", example="2025-03-20", description="請假開始日期"),
     *             @OA\Property(property="end_date", type="string", format="date", example="2025-03-22", description="請假結束日期"),
     *             @OA\Property(property="start_time", type="string", format="time", example="09:00", description="請假開始時間（可選）"),
     *             @OA\Property(property="end_time", type="string", format="time", example="18:00", description="請假結束時間（可選）"),
     *             @OA\Property(property="reason", type="string", example="家庭緊急狀況", description="請假原因"),
     *             @OA\Property(
     *                 property="attachments",
     *                 type="array",
     *                 @OA\Items(type="string", format="binary"),
     *                 description="上傳的附件（多個）"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="請假申請成功",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="請假申請成功")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="請假申請失敗（如時間衝突、假期時數不足、性別限制等）",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="此假別不適用於您的性別")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="無權限操作",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="員工資訊不存在")
     *         )
     *     )
     * )
     */
    public function requestLeave(Request $request)
    {
        $request->validate([
            'leave_type_id' => 'required|exists:leave_types,id',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
            'reason' => 'required|string',
            'attachments.*' => 'file|mimes:jpg,jpeg,png,pdf|max:2048'
        ]);

        $employee = auth()->user()->employee;
        if (!$employee) {
            return response()->json(['error' => '員工資訊不存在'], 403);
        }


        //  檢查請假類型的性別限制
        $leaveType = LeaveType::find($request->leave_type_id);
        if ($leaveType->gender_limit && $leaveType->gender_limit !== $employee->user->gender) {
            return response()->json(['error' => '此假別不適用於您的性別'], 400);
        }

        try {
            // **不再傳 hours，讓 MySQL 自動計算**
            DB::statement('CALL RequestLeave(?, ?, ?, ?, ?, ?, ?)', [
                $employee->id,
                $request->leave_type_id,
                $request->start_date,
                $request->end_date,
                $request->start_time ?? null,
                $request->end_time ?? null,
                $request->reason ?? null
            ]);

            // **取得剛剛申請的請假單**
            $leave = Leave::where('employee_id', $employee->id)
                ->latest('id')
                ->first();

            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('leave_attachments', 'public');

                    // **儲存附件**
                    File::create([
                        'user_id' => auth()->id(),
                        'leave_id' => $leave->id,
                        'leave_attachment' => $path
                    ]);
                }
            }

            return response()->json(['message' => '請假申請成功'], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }



    /**
     * @OA\Put(
     *     path="/leave/update/{id}",
     *     summary="更新請假申請",
     *     description="員工修改已提交但尚未審核的請假申請，支援更新時間、假別、附件等。若請假類型有性別限制，則需符合條件。",
     *     tags={"Leave"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="請假申請的 ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"leave_type_id", "start_date", "end_date"},
     *             @OA\Property(property="leave_type_id", type="integer", example=1, description="新的請假類型 ID"),
     *             @OA\Property(property="start_date", type="string", format="date", example="2025-03-20", description="新的開始日期"),
     *             @OA\Property(property="end_date", type="string", format="date", example="2025-03-22", description="新的結束日期"),
     *             @OA\Property(property="start_time", type="string", format="time", example="09:00", description="新的開始時間"),
     *             @OA\Property(property="end_time", type="string", format="time", example="18:00", description="新的結束時間"),
     *             @OA\Property(property="reason", type="string", example="家庭緊急狀況", description="請假原因"),
     *             @OA\Property(
     *                 property="remove_attachments",
     *                 type="array",
     *                 @OA\Items(type="integer"),
     *                 example={1,2},
     *                 description="要刪除的附件 ID 陣列"
     *             ),
     *             @OA\Property(
     *                 property="attachments",
     *                 type="array",
     *                 @OA\Items(type="string", format="binary"),
     *                 description="上傳的新附件 (多個)"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="請假申請更新成功",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="請假已更新")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="請假更新失敗（如時間衝突、假期時數不足、性別限制不符）",
     *         @OA\JsonContent(
     *             oneOf={
     *                 @OA\Property(property="error", type="string", example="請假時間與現有請假重疊"),
     *                 @OA\Property(property="error", type="string", example="假期時數不足"),
     *                 @OA\Property(property="error", type="string", example="此假別不適用於您的性別")
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="無權限操作",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="找不到員工資料")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="找不到請假申請",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="找不到請假記錄")
     *         )
     *     )
     * )
     */
    public function updateLeave(Request $request, $id)
    {
        $request->validate([
            'leave_type_id' => 'exists:leave_types,id',
            'start_date' => 'date|after_or_equal:today',
            'end_date' => 'date|after_or_equal:start_date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
            'reason' => 'string|nullable',
            'attachments.*' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048', // 支持多附件
            'remove_attachments' => 'nullable|array', // 支持刪除指定附件
            'remove_attachments.*' => 'integer|exists:files,id'
        ]);

        $employee = auth()->user()->employee;
        if (!$employee) {
            return response()->json(['error' => '找不到員工資料'], 403);
        }

        $leave = Leave::where('id', $id)->where('employee_id', $employee->id)->first();
        if (!$leave) {
            return response()->json(['error' => '找不到請假記錄'], 404);
        }

        // 🔹 **檢查請假類型的性別限制**
        $leaveType = LeaveType::find($request->leave_type_id);
        if ($leaveType && $leaveType->gender_limit && $leaveType->gender_limit !== $employee->user->gender) {
            return response()->json(['error' => '此假別不適用於您的性別'], 400);
        }

        // 🔹 **僅允許修改「主管批准但 HR 未批准」或「完全未審核」的請假**
        if ($leave->hr_status !== 'pending') {
            return response()->json(['error' => '已審核請假無法修改'], 400);
        }

        try {
            // 🔹 **調用預存程序計算時數**
            DB::statement('CALL UpdateLeaveRequest(?, ?, ?, ?, ?, ?, ?, ?)', [
                $leave->id,
                $request->leave_type_id ?? $leave->leave_type_id,
                $request->start_date ?? $leave->start_date,
                $request->end_date ?? $leave->end_date,
                $request->start_time ?? $leave->start_time,
                $request->end_time ?? $leave->end_time,
                $request->reason ?? $leave->reason,
                $employee->id
            ]);

            // 🔹 **處理附件刪除**
            if ($request->has('remove_attachments')) {
                foreach ($request->remove_attachments as $fileId) {
                    $file = File::where('id', $fileId)->where('leave_id', $leave->id)->first();
                    if ($file) {
                        Storage::delete('public/' . $file->leave_attachment);
                        $file->delete();
                    }
                }
            }

            // 🔹 **處理新附件**
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('leave_attachments', 'public');

                    File::create([
                        'user_id' => auth()->id(),
                        'leave_id' => $leave->id,
                        'leave_attachment' => $path
                    ]);
                }
            }

            return response()->json(['message' => '請假已更新'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }



    /**
     * @OA\Delete(
     *     path="/leave/cancel/{id}",
     *     summary="取消請假",
     *     description="員工取消請假申請。若假單已被批准，則恢復對應的請假餘額。請假已開始則無法取消。",
     *     tags={"Leave"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="請假單 ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="請假成功取消，並恢復餘額（若適用）",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="請假已取消，餘額已恢復")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="請假取消失敗（可能因假期已開始或餘額記錄錯誤）",
     *         @OA\JsonContent(
     *             oneOf={
     *                 @OA\Property(property="error", type="string", example="請假餘額記錄不存在，無法恢復時數"),
     *                 @OA\Property(property="error", type="string", example="已開始的請假無法取消")
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="無權限操作",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="找不到員工資料")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="請假記錄未找到",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="找不到請假記錄")
     *         )
     *     )
     * )
     */
    public function cancelLeave($id)
    {
        $employee = auth()->user()->employee;
        if (!$employee) {
            return response()->json(['error' => '找不到員工資料'], 403);
        }

        $leave = Leave::where('id', $id)->where('employee_id', $employee->id)->first();
        if (!$leave) {
            return response()->json(['error' => '找不到請假記錄'], 404);
        }

        // 🔹 **檢查是否已開始，若已開始則禁止取消**
        if ($leave->start_date < now()->toDateString()) {
            return response()->json(['error' => '已開始的請假無法取消'], 400);
        }

        // 🔹 **已批准的請假，需恢復餘額**
        if ($leave->final_status === 'approved') {
            $leaveBalance = LeaveBalance::where('employee_id', $leave->employee_id)
                ->where('leave_type_id', $leave->leave_type_id)
                ->first();

            if (!$leaveBalance) {
                return response()->json(['error' => '請假餘額記錄不存在，無法恢復時數'], 400);
            }

            // **恢復餘額**
            $leaveBalance->increment('remaining_hours', $leave->hours);
        }

        // 🔹 **標記為取消，並更新 `final_status`**
        $leave->update([
            'manager_status' => 'canceled',
            'hr_status' => 'canceled',
            'final_status' => 'canceled',
        ]);

        return response()->json(['message' => '請假已取消，餘額已恢復'], 200);
    }


    /**
     * @OA\Post(
     *     path="/api/leave/approve/manager/{id}",
     *     summary="主管審核請假申請",
     *     description="主管批准或拒絕請假申請，並填寫審核意見。",
     *     tags={"請假管理"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="請假申請 ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"status", "remarks"},
     *             @OA\Property(property="status", type="string", enum={"approved", "rejected"}, description="審核狀態 (approved=通過, rejected=拒絕)"),
     *             @OA\Property(property="remarks", type="string", description="審核意見 (必填)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="審核成功",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="主管審核已處理"),
     *             @OA\Property(property="manager_status", type="string", example="approved"),
     *             @OA\Property(property="manager_remarks", type="string", example="此員工假期符合規範，批准。"),
     *             @OA\Property(property="final_status", type="string", example="manager_approved")
     *         )
     *     ),
     *     @OA\Response(response=400, description="錯誤請求"),
     *     @OA\Response(response=403, description="無權限操作"),
     *     @OA\Response(response=404, description="找不到請假申請")
     * )
     */
    public function approveLeaveByManager(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:approved,rejected',
            'remarks' => 'required|string'
        ]);

        $leaveRequest = Leave::find($id);

        if (!$leaveRequest) {
            return response()->json(['error' => '找不到請假申請'], 404);
        }

        // **確保請假是待審核狀態**
        if ($leaveRequest->manager_status !== 'pending') {
            return response()->json(['error' => '此請假申請已由主管處理'], 400);
        }

        // **檢查該用戶是否為申請者的主管**
        $employee = $leaveRequest->employee;
        if (auth()->user()->id !== $employee->manager_id) {
            return response()->json(['error' => '您沒有權限審核此請假申請'], 403);
        }

        // **更新主管審核狀態**
        $leaveRequest->manager_status = $request->status;
        $leaveRequest->manager_remarks = $request->remarks;

        // **更新 `final_status`**
        if ($request->status === 'approved') {
            $leaveRequest->final_status = 'manager_approved'; // 等待 HR 最終審核
        } else {
            $leaveRequest->final_status = 'rejected'; // 主管直接拒絕，請假結束
        }

        $leaveRequest->save();

        return response()->json([
            'message' => '主管審核已處理',
            'manager_status' => $leaveRequest->manager_status,
            'manager_remarks' => $leaveRequest->manager_remarks,
            'final_status' => $leaveRequest->final_status
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/leave/approve/hr/{id}",
     *     summary="HR 審核請假申請",
     *     description="HR 批准或拒絕請假，批准時會扣除假期時數。",
     *     tags={"請假管理"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="請假申請 ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"status", "remarks"},
     *             @OA\Property(property="status", type="string", enum={"approved", "rejected"}, description="審核狀態 (approved=通過, rejected=拒絕)"),
     *             @OA\Property(property="remarks", type="string", description="審核意見 (必填)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="HR 審核成功",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="HR 最終審核已處理"),
     *             @OA\Property(property="hr_status", type="string", example="approved"),
     *             @OA\Property(property="hr_remarks", type="string", example="假期符合公司規範，批准。"),
     *             @OA\Property(property="final_status", type="string", example="approved")
     *         )
     *     ),
     *     @OA\Response(response=400, description="錯誤請求"),
     *     @OA\Response(response=403, description="無權限操作"),
     *     @OA\Response(response=404, description="找不到請假申請")
     * )
     */
    public function approveLeaveByHR(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:approved,rejected',
            'remarks' => 'required|string'
        ]);

        $leaveRequest = Leave::find($id);

        if (!$leaveRequest) {
            return response()->json(['error' => '找不到請假申請'], 404);
        }

        // **確保請假已經經過主管審核**
        if ($leaveRequest->manager_status !== 'approved') {
            return response()->json(['error' => '此請假尚未通過主管審核，無法進行 HR 審核'], 400);
        }

        // **確保 HR 尚未審核過**
        if ($leaveRequest->hr_status !== 'pending') {
            return response()->json(['error' => '此請假已由 HR 審核，無法再次修改'], 400);
        }

        // **檢查該用戶是否為 HR (部門 ID = 1)**
        $employee = auth()->user()->employee;
        if (!$employee || ($employee->department_id !== 1)) {
            return response()->json(['error' => '您沒有權限進行 HR 最終審核'], 403);
        }

        // **如果 HR 批准，從 `leave_balances` 扣除請假時數**
        if ($request->status === 'approved') {
            $leaveBalance = LeaveBalance::where('employee_id', $leaveRequest->employee_id)
                ->where('leave_type_id', $leaveRequest->leave_type_id)
                ->first();

            if (!$leaveBalance || $leaveBalance->remaining_hours < $leaveRequest->hours) {
                return response()->json(['error' => '假期時數不足，無法批准請假'], 400);
            }

            // **扣除餘額**
            $leaveBalance->decrement('remaining_hours', $leaveRequest->hours);
        }

        // **更新 HR 最終審核狀態**
        $leaveRequest->hr_status = $request->status;
        $leaveRequest->hr_remarks = $request->remarks;
        $leaveRequest->final_status = ($request->status === 'approved') ? 'approved' : 'rejected';
        $leaveRequest->save();

        return response()->json([
            'message' => 'HR 最終審核已處理',
            'hr_status' => $leaveRequest->hr_status,
            'hr_remarks' => $leaveRequest->hr_remarks,
            'final_status' => $leaveRequest->final_status
        ], 200);
    }



    /**
     * @OA\Put(
     *     path="/leave/correct/{id}",
     *     summary="HR 更正請假",
     *     description="HR 可更正已審核的請假單，包括修改假別、日期、時數等，並提供更正原因。若請假時數變更，則會自動更新請假餘額。",
     *     tags={"Leave"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="請假單 ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"status", "correction_reason"},
     *             @OA\Property(property="leave_type_id", type="integer", nullable=true, description="請假類型 ID", example=2),
     *             @OA\Property(property="start_date", type="string", format="date", nullable=true, description="請假開始日期", example="2025-04-10"),
     *             @OA\Property(property="end_date", type="string", format="date", nullable=true, description="請假結束日期", example="2025-04-12"),
     *             @OA\Property(property="start_time", type="string", format="time", nullable=true, description="請假開始時間", example="09:00"),
     *             @OA\Property(property="end_time", type="string", format="time", nullable=true, description="請假結束時間", example="18:00"),
     *             @OA\Property(property="hours", type="integer", nullable=true, description="請假總時數", example=16),
     *             @OA\Property(property="status", type="string", enum={"approved", "rejected"}, description="HR 更新的狀態", example="approved"),
     *             @OA\Property(property="correction_reason", type="string", maxLength=255, description="HR 更正原因（必填）", example="假單時間填寫錯誤，已更正")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="請假已成功更正，並更新請假餘額（如適用）",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="請假已更正"),
     *             @OA\Property(property="leave", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="leave_type_id", type="integer", example=2),
     *                 @OA\Property(property="start_date", type="string", format="date", example="2025-04-10"),
     *                 @OA\Property(property="end_date", type="string", format="date", example="2025-04-12"),
     *                 @OA\Property(property="start_time", type="string", format="time", example="09:00"),
     *                 @OA\Property(property="end_time", type="string", format="time", example="18:00"),
     *                 @OA\Property(property="hours", type="integer", example=16),
     *                 @OA\Property(property="hr_status", type="string", example="approved"),
     *                 @OA\Property(property="hr_remarks", type="string", example="假單時間填寫錯誤，已更正"),
     *                 @OA\Property(property="final_status", type="string", example="approved")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="請假時間與其他已批准假單重疊或假期餘額不足",
     *         @OA\JsonContent(
     *             oneOf={
     *                 @OA\Property(property="error", type="string", example="請假時間與其他已批准的假單重疊"),
     *                 @OA\Property(property="error", type="string", example="假期時數不足，無法更正")
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="無權限操作（非 HR 人員）",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="您沒有權限更正請假")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="請假記錄未找到",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="找不到請假記錄")
     *         )
     *     )
     * )
     */
    public function correctLeave(Request $request, $id)
    {
        $request->validate([
            'leave_type_id' => 'exists:leave_types,id',
            'start_date' => 'date|after_or_equal:today',
            'end_date' => 'date|after_or_equal:start_date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
            'hours' => 'integer|min:1',
            'status' => 'required|in:approved,rejected',
            'correction_reason' => 'required|string|max:255', // 更正原因
        ]);

        $leave = Leave::find($id);
        if (!$leave) {
            return response()->json(['error' => '找不到請假記錄'], 404);
        }

        // 🔹 **檢查 HR 身份**
        $employee = auth()->user()->employee;
        if (!$employee || ($employee->department_id !== 1)) {
            return response()->json(['error' => '您沒有權限更正請假'], 403);
        }

        // 🔹 **僅允許 HR 更正「已審核」的請假**
        if ($leave->final_status === 'pending' || $leave->final_status === 'manager_approved') {
            return response()->json(['error' => '待審核請假不可更正'], 400);
        }

        // 🔹 **檢查新時間是否與「已批准」的假單重疊**
        $overlappingApproved = Leave::where('employee_id', $leave->employee_id)
            ->where('id', '!=', $leave->id) // 排除當前請假
            ->where('final_status', 'approved') // 只考慮「已批准」的假單
            ->where(function ($query) use ($request) {
                $query->whereRaw('? BETWEEN start_date AND end_date', [$request->start_date])
                    ->orWhereRaw('? BETWEEN start_date AND end_date', [$request->end_date])
                    ->orWhereRaw('start_date BETWEEN ? AND ?', [$request->start_date, $request->end_date])
                    ->orWhereRaw('end_date BETWEEN ? AND ?', [$request->start_date, $request->end_date]);
            })
            ->exists();

        if ($overlappingApproved) {
            return response()->json(['error' => '請假時間與其他已批准的假單重疊'], 400);
        }

        // 🔹 **更新請假時數並調整請假餘額**
        if ($request->hours && $request->hours !== $leave->hours) {
            $leaveBalance = LeaveBalance::where('employee_id', $leave->employee_id)
                ->where('leave_type_id', $leave->leave_type_id)
                ->first();

            if (!$leaveBalance || ($leaveBalance->remaining_hours + $leave->hours < $request->hours)) {
                return response()->json(['error' => '假期時數不足，無法更正'], 400);
            }

            // **調整餘額**
            $leaveBalance->increment('remaining_hours', $leave->hours - $request->hours);
        }

        // 🔹 **更新請假資訊**
        $leave->update([
            'leave_type_id' => $request->leave_type_id ?? $leave->leave_type_id,
            'start_date' => $request->start_date ?? $leave->start_date,
            'end_date' => $request->end_date ?? $leave->end_date,
            'start_time' => $request->start_time ?? $leave->start_time,
            'end_time' => $request->end_time ?? $leave->end_time,
            'hours' => $request->hours ?? $leave->hours,
            'hr_status' => $request->status,
            'hr_remarks' => $request->correction_reason, // 記錄 HR 更正的原因
            'final_status' => $request->status // **同步更新 `final_status`**
        ]);

        return response()->json(['message' => '請假已更正', 'leave' => $leave], 200);
    }


    /**
     * @OA\Get(
     *     path="/leave/personal-records",
     *     summary="查詢個人請假紀錄",
     *     description="根據員工 ID 取得個人請假紀錄，支援依據假別、日期範圍篩選，確保跨日假單也能正確顯示。",
     *     tags={"Leave"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="leave_type_id",
     *         in="query",
     *         required=false,
     *         description="請假類型 ID（可選）",
     *         @OA\Schema(type="integer", example=2)
     *     ),
     *     @OA\Parameter(
     *         name="start_date",
     *         in="query",
     *         required=false,
     *         description="開始日期（可選）",
     *         @OA\Schema(type="string", format="date", example="2025-04-01")
     *     ),
     *     @OA\Parameter(
     *         name="end_date",
     *         in="query",
     *         required=false,
     *         description="結束日期（可選，需大於等於 start_date）",
     *         @OA\Schema(type="string", format="date", example="2025-04-30")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="成功返回請假紀錄",
     *         @OA\JsonContent(
     *             @OA\Property(property="leave_records", type="array", 
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="leave_type_id", type="integer", example=2),
     *                     @OA\Property(property="leave_type_name", type="string", example="特休假"),
     *                     @OA\Property(property="start_date", type="string", format="date", example="2025-04-10"),
     *                     @OA\Property(property="end_date", type="string", format="date", example="2025-04-12"),
     *                     @OA\Property(property="start_time", type="string", format="time", example="09:00"),
     *                     @OA\Property(property="end_time", type="string", format="time", example="18:00"),
     *                     @OA\Property(property="hours", type="integer", example=16),
     *                     @OA\Property(property="reason", type="string", example="家庭旅遊"),
     *                     @OA\Property(property="manager_status", type="string", example="approved"),
     *                     @OA\Property(property="manager_remarks", type="string", example="同意請假"),
     *                     @OA\Property(property="hr_status", type="string", example="approved"),
     *                     @OA\Property(property="hr_remarks", type="string", example="符合規定"),
     *                     @OA\Property(property="final_status", type="string", example="approved"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2025-03-25T08:30:00Z"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-03-26T10:00:00Z")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="日期範圍不正確（start_date > end_date）",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="結束日期需大於等於開始日期")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="未授權的請求（無員工資料）",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="找不到員工資料")
     *         )
     *     )
     * )
     */
    public function getPersonalLeaveRecords(Request $request)
    {
        $request->validate([
            'leave_type_id' => 'nullable|exists:leave_types,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $employee = auth()->user()->employee;
        if (!$employee) {
            return response()->json(['error' => '找不到員工資料'], 403);
        }

        // 執行 MySQL 預存程序
        $leaveRecords = DB::select('CALL GetPersonalLeaveRecords(?, ?, ?, ?)', [
            $employee->id,
            $request->leave_type_id ?? null,
            $request->start_date ?? null,
            $request->end_date ?? null,
        ]);

        return response()->json(['leave_records' => $leaveRecords], 200);
    }

    /**
     * @OA\Get(
     *     path="/leave/approvals",
     *     summary="查詢待審核請假申請",
     *     description="根據部門或員工 ID 查詢待審核的請假單，確保跨日假單能正確顯示。",
     *     tags={"Leave"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="department_id",
     *         in="query",
     *         required=false,
     *         description="部門 ID（可選，僅限 HR 或部門主管使用）",
     *         @OA\Schema(type="integer", example=3)
     *     ),
     *     @OA\Parameter(
     *         name="employee_id",
     *         in="query",
     *         required=false,
     *         description="員工 ID（可選）",
     *         @OA\Schema(type="integer", example=5)
     *     ),
     *     @OA\Parameter(
     *         name="start_date",
     *         in="query",
     *         required=false,
     *         description="開始日期（可選）",
     *         @OA\Schema(type="string", format="date", example="2025-04-01")
     *     ),
     *     @OA\Parameter(
     *         name="end_date",
     *         in="query",
     *         required=false,
     *         description="結束日期（可選，需大於等於 start_date）",
     *         @OA\Schema(type="string", format="date", example="2025-04-30")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="成功返回待審核請假單",
     *         @OA\JsonContent(
     *             @OA\Property(property="leave_applications", type="array", 
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="employee_id", type="integer", example=5),
     *                     @OA\Property(property="employee_name", type="string", example="王小明"),
     *                     @OA\Property(property="employee_number", type="string", example="EMP12345"),
     *                     @OA\Property(property="department_id", type="integer", example=3),
     *                     @OA\Property(property="department_name", type="string", example="資訊部"),
     *                     @OA\Property(property="leave_type_id", type="integer", example=2),
     *                     @OA\Property(property="leave_type_name", type="string", example="特休假"),
     *                     @OA\Property(property="start_date", type="string", format="date", example="2025-04-10"),
     *                     @OA\Property(property="end_date", type="string", format="date", example="2025-04-12"),
     *                     @OA\Property(property="start_time", type="string", format="time", example="09:00"),
     *                     @OA\Property(property="end_time", type="string", format="time", example="18:00"),
     *                     @OA\Property(property="hours", type="integer", example=16),
     *                     @OA\Property(property="reason", type="string", example="家庭旅遊"),
     *                     @OA\Property(property="manager_status", type="string", example="pending"),
     *                     @OA\Property(property="manager_remarks", type="string", example=""),
     *                     @OA\Property(property="hr_status", type="string", example="pending"),
     *                     @OA\Property(property="hr_remarks", type="string", example=""),
     *                     @OA\Property(property="final_status", type="string", example="pending"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2025-03-25T08:30:00Z"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-03-26T10:00:00Z")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="日期範圍不正確（start_date > end_date）",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="結束日期需大於等於開始日期")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="未授權的請求（無員工資料）",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="找不到員工資料")
     *         )
     *     )
     * )
     */
    public function getLeaveApplicationsForApproval(Request $request)
    {
        $request->validate([
            'department_id' => 'nullable|exists:departments,id',
            'employee_id' => 'nullable|exists:employees,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $user = auth()->user();
        $employee = $user->employee;

        if (!$employee) {
            return response()->json(['error' => '找不到員工資料'], 403);
        }

        // 執行 MySQL 預存程序
        $leaveApplications = DB::select('CALL GetLeaveApplicationsForApproval(?, ?, ?, ?, ?)', [
            $user->id,
            $request->department_id ?? null,
            $request->employee_id ?? null,
            $request->start_date ?? null,
            $request->end_date ?? null,
        ]);

        return response()->json(['leave_applications' => $leaveApplications], 200);
    }
}

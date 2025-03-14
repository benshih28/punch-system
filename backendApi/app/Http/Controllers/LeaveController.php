<?php

namespace App\Http\Controllers;

use App\Http\Requests\LeaveApplyRequest; // Ensure this class exists in the specified namespace
use App\Http\Requests\LeaveListRequest;
use App\Http\Requests\LeaveUpdateRequest;
use App\Models\Employee; // 確保引入 Employee 模型
use App\Models\LeaveType; // 確保引入 LeaveType 模型
use App\Services\LeaveService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\Leave;
use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Str;


class LeaveController extends Controller
{
    protected $leaveService;

    public function __construct(LeaveService $leaveService)
    {
        $this->leaveService = $leaveService;
    }

    // 1. 員工申請請假
    public function requestLeave(LeaveApplyRequest $request): JsonResponse
    {
        try {
            // 1️⃣ 透過 JWT 取得當前登入者
            $user = auth()->user();
            $leaveType = LeaveType::find($request->input('leave_type_id'));

            // 2️⃣ **資料驗證**
            $data = $request->validated();
            $data['user_id'] = $user->id; // 由後端自動填入 `user_id`
            $data['status'] = 0;
            $data['attachment'] = null; // **預設 `attachment` 為 `null`，避免未定義錯誤**

            // **如果是假別是生理假，但使用者不是女性，則拒絕請假**
            if ($leaveType->name === 'Menstrual Leave' && $user->gender !== 'female') {
                return response()->json(['message' => '您無法申請生理假'], 403);
            }

            // 3️⃣ **處理附件**（如果沒有附件，`attachment` 保持 `null`）
            $fileRecord = null;
            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');

                // 產生唯一檔名
                $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
                $attachmentPath = $file->storeAs('attachments', $filename, 'public');

                // **存入 `files` 表**
                $fileRecord = File::create([
                    'user_id' => $user->id,
                    'leave_id' => null, // 先不關聯 `leave_id`，稍後再更新
                    'leave_attachment' => str_replace('public/', '', $attachmentPath), // ✅ 存成相對路徑
                ]);

                // **將附件 ID 存入 `$data`，傳遞給 Service**
                $data['attachment'] = $fileRecord->id;
            }

            // 4️⃣ **呼叫 Service 層處理請假**
            $leave = $this->leaveService->applyLeave($data);

            // 5️⃣ **如果有附件，更新 `leave_id` 到 `File` 表**
            if ($fileRecord) {
                $fileRecord->update(['leave_id' => $leave->id]);
            }

            // 6️⃣ **回傳成功資訊**
            return response()->json([
                'message' => '申請成功，假單已送出',
                'leave' => $this->formatLeave($leave),
            ], 201); // **201 Created：表示成功建立新資源**

        } catch (\Throwable $e) {
            // 7️⃣ **回傳錯誤資訊**
            return response()->json([
                'message' => '申請失敗，請檢查填入資料是否有誤',
                'error' => app()->isLocal() ? $e->getMessage() : null, // **本機開發環境才回傳錯誤**
            ], 500);
        }
    }

    // 2. 查詢個人請假紀錄 (員工)
    public function viewMyLeaveRecords(LeaveListRequest $request): JsonResponse
    {
        $user = auth()->user();
        if ($user->employee->status === 'inactive') {
            return response()->json(['message' => '無此權限，無法查詢請假紀錄'], 403);
        }

        try {
            $filters = $request->validated();

            // Log::info('查詢請假紀錄', ['user_id' => $user->id, 'filters' => $filters]);

            $leaves = $this->leaveService->getLeaveList($user, $filters);

            if ($leaves->isEmpty()) {
                return response()->json([
                    'message' => '查無符合條件的資料',
                    'records' => [],
                ], 200);
            }

            return response()->json([
                'message' => '查詢成功',
                'records' => $leaves->map(fn($leave) => $this->formatLeave($leave)),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => app()->isLocal() ? $e->getMessage() : '查詢失敗',
            ], 500);
        }
    }

    // 3. 查詢「部門」請假紀錄（主管 & HR）
    public function viewDepartmentLeaveRecords(Request $request): JsonResponse
    {
        $user = auth()->user();

        if ($user->employee->status === 'inactive') {
            return response()->json(['message' => '無此權限，無法查詢請假紀錄'], 403);
        }

        try {
            $filters = $request->validate([
                'leave_type_id' => 'nullable|exists:leave_types,id',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'status' => 'nullable|integer|in:0,1,2,3,4',
            ]);

            // Log::info('查詢部門請假紀錄', ['user_id' => $user->id, 'filters' => $filters]);

            $leaves = $this->leaveService->getDepartmentLeaveList($user, $filters);

            if ($leaves->total() === 0) {
                return response()->json([
                    'message' => '查無符合條件的請假紀錄',
                    'records' => [],
                    'total' => 0,
                ], 200);
            }

            return response()->json([
                'message' => '查詢成功',
                'records' => $leaves->map(fn($leave) => $this->formatLeave($leave)),
                'total' => $leaves->total(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => app()->isLocal() ? $e->getMessage() : '查詢失敗',
            ], 500);
        }
    }

    // 4. 查詢「全公司」請假紀錄 (HR)
    public function viewCompanyLeaveRecords(Request $request): JsonResponse
    {
        $user = auth()->user();
        if ($user->employee->status === 'inactive') {
            return response()->json(['message' => '無此權限，無法查詢請假紀錄'], 403);
        }

        try {
            // ✅ 查詢所有請假紀錄
            $filters = $request->validate([
                'leave_type_id' => 'nullable|exists:leave_types,id',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'status' => 'nullable|integer|in:0,1,2,3,4',
            ]);

            $leaves = $this->leaveService->getCompanyLeaveList($filters);
            $formattedRecords = $leaves->map(fn($leave) => $this->formatLeave($leave));
            return response()->json([
                'message' => '查詢成功',
                'records' => $formattedRecords,
                'pagination' => [
                    'total' => $leaves->total(),
                    'per_page' => $leaves->perPage(),
                    'current_page' => $leaves->currentPage(),
                    'last_page' => $leaves->lastPage(),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => '查詢失敗，請稍後再試',
            ], 500);
        }
    }

    // 5. 修改請假申請 (HR、員工)
    public function updateLeave(LeaveUpdateRequest $request, int $id): JsonResponse
    {
        try {
            $user = auth()->user();

            // 1️⃣ 取得請假紀錄
            $leave = Leave::where('id', $id)
                ->where('user_id', $user->id)
                ->first();

            if (!$leave) {
                return response()->json(['message' => '查無此假單或您無權限修改'], 403);
            }

            // 2️⃣ **處理附件**（如果沒有新附件，保持原本的 `attachment_id`）
            $fileRecord = null;
            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');

                // 產生唯一檔名
                $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
                $attachmentPath = $file->storeAs('attachments', $filename, 'public');

                // 取得舊附件
                $oldFile = File::find($leave->attachment);

                // **刪除舊附件檔案**
                if ($oldFile && Storage::exists($oldFile->leave_attachment)) {
                    Storage::delete($oldFile->leave_attachment);
                    Log::info("成功刪除舊附件: " . $oldFile->leave_attachment);
                }

                // **更新舊 `File` 紀錄，或新增新附件**
                if ($oldFile) {
                    $oldFile->update(['leave_attachment' => $attachmentPath]);
                    $fileRecord = $oldFile;
                } else {
                    $fileRecord = File::create([
                        'user_id' => $user->id,
                        'leave_id' => $leave->id,
                        'leave_attachment' => "storage/" . $attachmentPath,
                    ]);
                }
            }

            // 3️⃣ **呼叫 Service 層更新請假**
            $updatedLeave = $this->leaveService->updateLeave($leave, [
                'leave_type' => $request->input('leave_type'),
                'start_time' => $request->input('start_time'),
                'end_time' => $request->input('end_time'),
                'reason' => $request->input('reason'),
                'status' => $request->input('status'),
                'attachment' => $fileRecord ? $fileRecord->id : $leave->attachment, // **如果有新附件就更新，否則保持原值**
            ]);

            // 4️⃣ **回傳成功訊息**
            return response()->json([
                'message' => '假單更新成功',
                'leave' => $this->formatLeave($updatedLeave),
            ], 200);
        } catch (\Exception $e) {
            // 5️⃣ **回傳錯誤訊息**
            return response()->json([
                'message' => app()->isLocal() ? $e->getMessage() : '更新失敗，請重新檢查資料格式是否錯誤',
            ], 500);
        }
    }


    // 6. 刪除請假申請 (HR、員工)
    public function deleteLeave(int $id): JsonResponse
    {
        try {
            $user = auth()->user();  // 取得當前登入的使用者

            // 1️⃣ **取得請假紀錄**
            $leave = Leave::find($id);

            if (!$leave) {
                Log::warning("刪除請假失敗 - 找不到假單或無權限", ['user_id' => $user->id, 'leave_id' => $id]);
                return response()->json(['message' => '查無此假單或您無權限刪除'], 403);
            }

            // 2️⃣ **刪除相關附件**
            if (!empty($leave->attachment)) {
                $file = File::find($leave->attachment);
                if ($file) {
                    $filePath = $file->leave_attachment;

                    // **刪除實體檔案**
                    if ($filePath && Storage::exists($filePath)) {
                        Storage::delete($filePath);
                        Log::info("成功刪除附件檔案: " . $filePath);
                    }

                    // **刪除 `files` 表中的紀錄**
                    $file->delete();
                    // Log::info("成功刪除 files 記錄", ['file_id' => $file->id]);
                }
            }

            // 3️⃣ **刪除請假申請**
            $leave->delete();
            // Log::info("成功刪除假單", ['user_id' => $user->id, 'leave_id' => $id]);

            // 4️⃣ **回傳成功訊息**
            return response()->json(['message' => '假單刪除成功'], 200);
        } catch (\Exception $e) {
            // 5️⃣ **回傳錯誤訊息**
            return response()->json([
                'message' => app()->isLocal() ? $e->getMessage() : '系統發生錯誤，請稍後再試',
            ], 500);
        }
    }

    // 7. 取得特殊假別剩餘小時數 (計算假夠不夠扣)
    public function getRemainingLeaveHours($leaveTypeId)
    {
        try {
            $user = auth()->user(); // 取得當前用戶
            $remainingHours = $this->leaveService->getRemainingLeaveHours($leaveTypeId, $user->id);

            return response()->json([
                'remaining_hours' => $remainingHours,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 500);
        }
    }

    // 8. 資料格式統一，讓回傳結果都長一樣 ✅    
    private function formatLeave($leave): array
    {
        return [
            'leave_id' => $leave->id,
            'user_id' => $leave->user_id,
            'user_name' => $leave->user->name,
            'leave_type' => optional($leave->leaveType)->name, // 確保讀取關聯名稱
            'start_time' => $leave->start_time,
            'end_time' => $leave->end_time,
            'reason' => $leave->reason,
            'status' => $leave->status,
            'attachment' => $leave->file ? asset("storage/" . $leave->file->leave_attachment) : null,
        ];
    }

    // 9.部門主管審核通過
    public function approveDepartmentLeave(Request $request, $id)
    {
        $leave = Leave::findOrFail($id);
        $user = auth()->user();

        // 確保使用者擁有 `approve_department_leave` 權限
        if (!$user->can('approve_department_leave')) {
            return response()->json(['error' => '你沒有權限審核本部門請假單'], 403);
        }

        // 找出請假員工
        $leaveEmployee = Employee::where('user_id', $leave->user_id)->first();
        if (!$leaveEmployee) {
            return response()->json(['error' => '查無此員工'], 404);
        }

        // 確保主管只能審核自己部門的員工
        $departmentId = Employee::where('user_id', $user->id)->value('department_id');
        if ($departmentId !== $leaveEmployee->department_id) {
            return response()->json(['error' => '你只能審核自己部門的員工'], 403);
        }

        // 確保請假單是待審核狀態
        if ($leave->status !== 0) {
            return response()->json(['error' => '此假單已審核，無法修改'], 403);
        }

        // 更新狀態為 主管批准
        $leave->status = 1;
        $leave->approved_by = $user->id;
        $leave->save();

        return response()->json(['message' => '假單已被主管審核，等待 HR 審核']);
    }

    // 10.部門主管審核拒絕
    public function rejectDepartmentLeave(Request $request, $id)
    {
        $leave = Leave::findOrFail($id);
        $user = auth()->user();

        // 確保使用者擁有 `approve_department_leave` 權限
        if (!$user->can('approve_department_leave')) {
            return response()->json(['error' => '你沒有權限駁回請假單'], 403);
        }

        // 找出請假員工
        $leaveEmployee = Employee::where('user_id', $leave->user_id)->first();
        if (!$leaveEmployee) {
            return response()->json(['error' => '查無此員工'], 404);
        }

        // 確保主管只能審核自己部門的員工
        $departmentId = Employee::where('user_id', $user->id)->value('department_id');
        if ($departmentId !== $leaveEmployee->department_id) {
            return response()->json(['error' => '你只能拒絕自己部門的員工'], 403);
        }

        // 確保請假單是待審核狀態
        if ($leave->status !== 0) {
            return response()->json(['error' => '此假單已審核，無法修改'], 403);
        }

        // 確保拒絕理由存在
        $rejectReason = $request->input('reject_reason');
        if (!$rejectReason) {
            return response()->json(['error' => '請填寫拒絕原因'], 400);
        }

        // 更新狀態為 主管拒絕
        $leave->status = 2;
        $leave->reject_reason = $rejectReason;
        $leave->approved_by = $user->id;
        $leave->save();

        return response()->json(['message' => '假單已被主管拒絕']);
    }

    // 11.HR審核通過
    public function approveLeave(Request $request, $id)
    {
        $leave = Leave::findOrFail($id);
        $user = auth()->user();

        // 確保使用者擁有 `approve_leave` 權限
        if (!$user->can('approve_leave')) {
            return response()->json(['error' => '你沒有權限最終批准假單'], 403);
        }

        // 確保請假單尚未被批准
        if ($leave->status === 3 || $leave->status === 4) {
            return response()->json(['error' => '此假單已被批准，不可重複審核'], 403);
        }

        // 確保請假單已經經過主管審核
        if ($leave->status !== 1) {
            return response()->json(['error' => '請假單必須先經過主管審核'], 403);
        }

        // 更新狀態為 HR 批准
        $leave->status = 3;
        $leave->approved_by = $user->id;
        $leave->save();

        return response()->json(['message' => '假單已最終批准']);
    }

    // 12.HR審核拒絕
    public function rejectLeave(Request $request, $id)
    {
        $leave = Leave::findOrFail($id);
        $user = auth()->user();

        // 確保使用者擁有 `approve_leave` 權限
        if (!$user->can('approve_leave')) {
            return response()->json(['error' => '你沒有權限駁回假單'], 403);
        }

        // 確保請假單已經經過主管審核
        if ($leave->status !== 1) {
            return response()->json(['error' => '請假單必須先經過主管審核'], 403);
        }

        // 不能駁回已經被拒絕的請假
        if ($leave->status === 4) {
            return response()->json(['error' => '此假單已被拒絕'], 403);
        }

        // 確保拒絕理由存在
        $rejectReason = $request->input('reject_reason');
        if (!$rejectReason) {
            return response()->json(['error' => '請填寫拒絕原因'], 400);
        }

        // 更新狀態為 HR 拒絕
        $leave->status = 4;
        $leave->reject_reason = $rejectReason;
        $leave->approved_by = $user->id;
        $leave->save();

        return response()->json(['message' => '假單已被 HR 拒絕']);
    }
}

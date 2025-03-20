<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Schema(
 *     schema="PunchCorrection",
 *     title="Punch Correction",
 *     description="打卡補登請求資料結構",
 *     type="object",
 *     required={"id", "user_id", "correction_type", "punch_time", "status"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="user_id", type="integer", example=5),
 *     @OA\Property(property="user_name", type="string", example="Admin"),
 *     @OA\Property(property="department_id", type="integer", example=2),
 *     @OA\Property(property="department_name", type="string", example="資訊部"),
 *     @OA\Property(property="punch_time", type="string", format="date-time", example="2025-03-11 08:00:00"),
 *     @OA\Property(property="correction_type", type="string", enum={"punch_in", "punch_out"}, example="punch_in"),
 *     @OA\Property(property="reason", type="string", example="忘記打卡"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2025-03-19 20:30:33"),
 *     @OA\Property(property="status", type="string", enum={"pending", "approved", "rejected"}, example="approved"),
 *     @OA\Property(property="review_message", type="string", example="審核通過"),
 * )
 * @OA\Schema(
 *     schema="AttendanceRecord",
 *     title="Attendance Record",
 *     description="打卡紀錄的回應格式",
 *     type="object",
 *     required={"user_id", "user_name", "date", "punch_in", "punch_out"},
 *     @OA\Property(property="user_id", type="integer", example=1),
 *     @OA\Property(property="user_name", type="string", example="王小明"),
 *     @OA\Property(property="date", type="string", format="date", example="2025-03-11"),
 *     @OA\Property(property="punch_in", type="string", format="date-time", nullable=true, example="2025-03-11 08:00:00"),
 *     @OA\Property(property="punch_out", type="string", format="date-time", nullable=true, example="2025-03-11 17:00:00")
 * )
 */
class PunchCorrection extends Model
{
    protected $fillable = [
        'user_id',
        'correction_type',
        'punch_time',
        'reason',
        'status',
        'review_message',
        'approved_by',
        'approved_at',
    ];

    // 補登記錄所屬的使用者（申請人）
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // 補登記錄的審核者（管理者）
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}

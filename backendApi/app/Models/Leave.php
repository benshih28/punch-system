<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Leave extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'leave_type',
        'start_time',
        'end_time',
        'leave_hours',
        'reason',
        'reject_reason',
        'status'
    ];

    public const TYPES = [
        'personal',
        'sick',
        'official',
        'marriage',
        'maternity',
        'funeral',
        'annual',
        'menstrual',
    ];

    public const STATUSES = [
        'pending',
        'approved',
        'rejected',
    ];

    /**
     * 假別對應中文
     */
    public static function typeLabel($type)
    {
        return match ($type) {
            'personal' => '事假',
            'sick' => '病假',
            'official' => '公假',
            'marriage' => '婚假',
            'maternity' => '產假',
            'funeral' => '喪假',
            'annual' => '特休假',
            'menstrual' => '生理假',
            default => '未知假別',
        };
    }

    /**
     * 狀態對應中文
     */
    public static function statusLabel($status)
    {
        return match ($status) {
            'pending' => '待審核',
            'approved' => '已審核',
            'rejected' => '已退回',
            default => '未知狀態',
        };
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

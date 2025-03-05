<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

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

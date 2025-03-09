<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Leave extends Model
{
    use HasFactory;
    
    protected $table = 'leaves';

    protected $fillable = [
        'user_id',
        'leave_type_id',
        'start_time',
        'end_time',
        'leave_hours',
        'reason',
        'reject_reason',
        'status',
        'attachment',
    ];

    public const STATUSES = [
        'pending',
        'approved',
        'rejected',
    ];

    // 使用者申請請假
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // 附件
    public function files()
    {
        return $this->hasMany(File::class);
    }

    // 假別
    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class, 'leave_type_id');
    }
}

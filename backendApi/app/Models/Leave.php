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
        'status',
        'attachment',
    ];

    public const STATUSES = [
        'pending',
        'approved',
        'rejected',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

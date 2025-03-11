<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\LeaveType;

class LeaveResetRule extends Model
{
    use HasFactory;

    protected $table = 'leave_reset_rules'; // 指定資料表名稱

    protected $fillable = [
        'leave_type_id',
        'rule_type',
        'rule_value',
    ];

    /**
     * 關聯 `leave_types` 表（一對一）
     */
    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class, 'leave_type_id');
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Leave extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'leave_type_id', // **修正：改為 `leave_type_id` 而非 `type`**
        'start_date',
        'end_date',
        'start_time', // **新增開始時間**
        'end_time', // **新增結束時間**
        'hours', // **新增請假總時數**
        'reason',
        'manager_status',
        'manager_remarks',
        'hr_status',
        'hr_remarks',
        'final_status',
    ];

    /**
     * **關聯：請假單屬於某位員工**
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * **關聯：請假單屬於某個假別**
     */
    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function files()
    {
        return $this->hasMany(File::class, 'leave_id');
    }
}

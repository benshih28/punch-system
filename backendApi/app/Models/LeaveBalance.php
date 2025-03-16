<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveBalance extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id', // **員工 ID**
        'leave_type_id', // **假別 ID**
        'remaining_hours' // **剩餘假期時數**
    ];

    /**
     * **關聯：每筆假期餘額屬於某位員工**
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * **關聯：每筆假期餘額對應一個請假類型**
     */
    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }
}

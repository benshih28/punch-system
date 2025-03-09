<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LeaveResetRule extends Model
{
    // 假規資料表
    use HasFactory;

    protected $fillable = ['leave_type_id', 'rule_type', 'rule_value'];

    // 關聯到leave_type_id
    public function leaveType()
{
    return $this->belongsTo(LeaveType::class);
}

}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LeaveResetRule extends Model
{
    //
    use HasFactory;

    protected $fillable = ['leave_type_id', 'rule_type', 'rule_value'];

    public function leaveType()
{
    return $this->belongsTo(LeaveType::class);
}

}

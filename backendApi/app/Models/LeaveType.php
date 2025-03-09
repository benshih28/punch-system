<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class LeaveType extends Model
{
    use HasFactory;

    // 允許批量填充的欄位
    protected $fillable = ['name', 'description', 'total_hours'];

    // 假別
    public function leaves()
    {
        return $this->hasMany(Leave::class, 'leave_type_id');
    }

    // 假規
    public function resetRules()
{
    return $this->hasMany(LeaveResetRule::class);
}
}


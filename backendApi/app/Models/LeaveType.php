<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Leave;
use App\Models\LeaveResetRule;


class LeaveType extends Model
{
    use HasFactory;

    // 新增假別表
    protected $fillable = ['name', 'description', 'total_hours'];

    // 定義和 leaves 表的關聯（假設 leaves 有 leave_type_id）
    public function leaves()
    {
        return $this->hasMany(Leave::class, 'leave_type_id');
    }

    public function resetRules()
{
    return $this->hasMany(LeaveResetRule::class);
}
}
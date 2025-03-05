<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveType extends Model
{
    use HasFactory;

    // 允許批量填充的欄位
    protected $fillable = ['id', 'name', 'description'];

    // 定義和 leaves 表的關聯（假設 leaves 有 leave_type_id）
    public function leaves()
    {
        return $this->hasMany(Leave::class, 'leave_type_id');
    }
}


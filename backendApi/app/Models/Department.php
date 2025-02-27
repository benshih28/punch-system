<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'manager_id'];

    // 取得部門的主管 (關聯到 users 表)
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id', 'id');
    }

    // 部門下的所有職位 (1對多)
    public function positions(): HasMany
    {
        return $this->hasMany(Position::class, 'department_id', 'id');
    }
}
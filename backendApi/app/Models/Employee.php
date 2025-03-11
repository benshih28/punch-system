<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'department_id', 'position_id', 'manager_id', 'status'];

    protected $with = ['user', 'department', 'position', 'manager'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    // 關連到employee_profile資料表
    public function profile()
    {
        return $this->hasOne(EmployeeProfile::class, 'employee_id');
    }

    // 加入employee創建日期
    protected static function boot()
    {
        parent::boot();

        // 監聽 Employee 狀態變更
        static::updated(function ($employee) {
            // 檢查是否從非 'approved' 狀態變為 'approved'
            if ($employee->status === 'approved' && $employee->wasChanged('status')) {
                // 確保 `EmployeeProfile` 還不存在，避免重複建立
                if (!$employee->profile) {
                    EmployeeProfile::create([
                        'employee_id' => $employee->id,
                        'hire_date' => now()->toDateString(),
                    ]);
                }
            }
        });
    }
}

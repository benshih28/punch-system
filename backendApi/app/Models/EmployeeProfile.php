<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class EmployeeProfile extends Model
{
    use HasFactory;

    protected $fillable = ['employee_id', 'hire_date'];
    protected $casts = [
        'hire_date' => 'date',
    ];    

    // 🔹 關聯 Employee
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    // ⭐ 自動計算年資
    public function getYearsOfServiceAttribute()
    {
        return $this->hire_date ? Carbon::parse($this->hire_date)->diffInYears(Carbon::now()) : null;
    }

}

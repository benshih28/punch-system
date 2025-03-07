<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeProfile extends Model
{
    protected $fillable = ['employee_id', 'hire_date'];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}

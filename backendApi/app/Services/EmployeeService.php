<?php

namespace App\Services;

use App\Models\Employee;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class EmployeeService
{
    public function createEmployee(array $data): Employee
    {
        return Employee::create($data);  // 純建Employee
    }

    public function triggerAddEmployeeProfile(int $employeeId): void
    {
        Log::info('🚀 呼叫AddEmployeeProfile Stored Procedure', ['employee_id' => $employeeId]);
        DB::statement("CALL AddEmployeeProfile(?)", [$employeeId]);
    }
}

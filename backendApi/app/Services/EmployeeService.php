<?php

namespace App\Services;
 
use App\Models\Employee; 
use Illuminate\Support\Facades\DB; 
use Illuminate\Support\Facades\Log;
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



    /**
     * 註冊新員工（使用 MySQL 預存程序）
     */
    public function createEmployee($name, $email, $password, $gender)
    {
        return DB::statement('CALL CreateEmployee(?, ?, ?, ?)', [
            $name, 
            strtolower($email), 
            bcrypt($password),
            $gender
        ]);
    }

    /**
     * HR 批准 / 拒絕 員工
     */
    public function reviewEmployee($id, $status)
    {
        return DB::statement('CALL ReviewEmployee(?, ?)', [$id, $status]);
    }

    /**
     * HR 分配部門、職位、主管
     */
    public function assignDepartmentAndPosition($id, $departmentId, $positionId, $managerId)
    {
        return DB::statement('CALL AssignEmployeeDetails(?, ?, ?, ?)', [$id, $departmentId, $positionId, $managerId]);
    }

    /**
     * HR 刪除員工
     */
    public function deleteEmployee($id)
    {
        return DB::statement('CALL DeleteEmployee(?)', [$id]);
    }

    /**
     * 取得員工列表
     */
    public function getEmployees($departmentId, $roleId, $userId, $perPage, $offset)
    {
        return DB::select('CALL GetEmployees(?, ?, ?, ?, ?)', [
            $departmentId ?: null, 
            $roleId ?: null,  
            $userId ?: null, 
            $perPage, 
            $offset
        ]);
    }

    /**
     * 查詢員工的主管
     */
    public function getEmployeeManager($id)
    {
        return Employee::with('manager')->find($id);
    }

    /**
     * 主管查詢自己管理的員工
     */
    public function getEmployeesByManager($managerId)
    {
        return Employee::where('manager_id', $managerId)->get();
    }
}

?>




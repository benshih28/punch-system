<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Employee;
class EnsureEmployeeIsApproved
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        // 🔹 確保員工存在，且 `status` 為 `approved`
        $employee = Employee::where('user_id', $user->id)->first();

        if (!$employee || $employee->status !== 'approved') {
            return response()->json(['error' => '您的帳號尚未通過審核'], 403);
        }
        return $next($request);
    }
}

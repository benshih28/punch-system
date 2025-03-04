<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Employee;

class IsManagerMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        // 確認使用者是否為某些員工的 `manager_id`
        $isManager = Employee::where('manager_id', $user->id)->exists();

        if (!$isManager) {
            return response()->json(['error' => '無權限'], 403);
        }

        return $next($request);
    }
}


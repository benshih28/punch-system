<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Permission; // Import the Permission model

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {

        //✅新增路由判斷身分的GATE
        Gate::define('isHRorAdmin', function ($user) {
            return $user->hasAnyRole(['HR', 'Admin']);
        });

        // ✅ 允許根據 `permissions` 來授權
        foreach (Permission::all() as $permission) {
            Gate::define($permission->name, function ($user) use ($permission) {
                return $user->hasPermissionTo($permission->name);
            });
        }

        // ✅ 允許 HR 審核通過的員工 (`status = approved`) 才能上傳/獲取大頭貼
        Gate::define('upload_avatar', function ($user) {
            return $user->employee && $user->employee->status === 'approved';
        });

        Gate::define('view_avatar', function ($user) {
            return $user->employee && $user->employee->status === 'approved';
        });


        ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            return config('app.frontend_url')."/password-reset/$token?email={$notifiable->getEmailForPasswordReset()}";
        });
    }
}

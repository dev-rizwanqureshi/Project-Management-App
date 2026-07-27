<?php

namespace App\Providers;

use App\Repositories\Contracts\AdminAuthRepositoryInterface;
use App\Repositories\Contracts\AdminDashboardRepositoryInterface;
use App\Repositories\Contracts\AdminPlatformListingRepositoryInterface;
use App\Repositories\Contracts\AdminRoleManagementRepositoryInterface;
use App\Repositories\Contracts\AdminSettingsRepositoryInterface;
use App\Repositories\Contracts\AdminStaffRepositoryInterface;
use App\Repositories\Contracts\AdminUserManagementRepositoryInterface;
use App\Repositories\Contracts\AuthRepositoryInterface;
use App\Repositories\Contracts\CompanyListingRepositoryInterface;
use App\Repositories\Contracts\CompanyMembershipRepositoryInterface;
use App\Repositories\Contracts\CompanySetupRepositoryInterface;
use App\Repositories\Contracts\DashboardRepositoryInterface;
use App\Repositories\Contracts\RoleManagementRepositoryInterface;
use App\Repositories\Contracts\SettingsRepositoryInterface;
use App\Repositories\Eloquent\AdminAuthRepository;
use App\Repositories\Eloquent\AdminDashboardRepository;
use App\Repositories\Eloquent\AdminPlatformListingRepository;
use App\Repositories\Eloquent\AdminRoleManagementRepository;
use App\Repositories\Eloquent\AdminSettingsRepository;
use App\Repositories\Eloquent\AdminStaffRepository;
use App\Repositories\Eloquent\AdminUserManagementRepository;
use App\Repositories\Eloquent\AuthRepository;
use App\Repositories\Eloquent\CompanyListingRepository;
use App\Repositories\Eloquent\CompanyMembershipRepository;
use App\Repositories\Eloquent\CompanySetupRepository;
use App\Repositories\Eloquent\DashboardRepository;
use App\Repositories\Eloquent\RoleManagementRepository;
use App\Repositories\Eloquent\SettingsRepository;
use App\Services\AuthService;
use App\Services\Contracts\AuthServiceInterface;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AuthRepositoryInterface::class, AuthRepository::class);
        $this->app->bind(AdminAuthRepositoryInterface::class, AdminAuthRepository::class);
        $this->app->bind(DashboardRepositoryInterface::class, DashboardRepository::class);
        $this->app->bind(AdminDashboardRepositoryInterface::class, AdminDashboardRepository::class);
        $this->app->bind(RoleManagementRepositoryInterface::class, RoleManagementRepository::class);
        $this->app->bind(AdminRoleManagementRepositoryInterface::class, AdminRoleManagementRepository::class);
        $this->app->bind(AdminSettingsRepositoryInterface::class, AdminSettingsRepository::class);
        $this->app->bind(AdminStaffRepositoryInterface::class, AdminStaffRepository::class);
        $this->app->bind(AdminPlatformListingRepositoryInterface::class, AdminPlatformListingRepository::class);
        $this->app->bind(AdminUserManagementRepositoryInterface::class, AdminUserManagementRepository::class);
        $this->app->bind(CompanyListingRepositoryInterface::class, CompanyListingRepository::class);
        $this->app->bind(CompanyMembershipRepositoryInterface::class, CompanyMembershipRepository::class);
        $this->app->bind(CompanySetupRepositoryInterface::class, CompanySetupRepository::class);
        $this->app->bind(SettingsRepositoryInterface::class, SettingsRepository::class);
        $this->app->bind(AuthServiceInterface::class, AuthService::class);
    }
}

<?php

namespace App\Repositories\Eloquent;

use App\Models\Admin;
use App\Repositories\Contracts\AdminAuthRepositoryInterface;
use App\Services\AdminRolePermissionDefaults;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminAuthRepository implements AdminAuthRepositoryInterface
{
    public function __construct(
        private readonly AdminRolePermissionDefaults $adminRolePermissionDefaults,
    ) {}

    /**
     * @param  array<string, mixed>  $credentials
     */
    public function attemptLogin(array $credentials, Request $request): bool
    {
        $remember = (bool) ($credentials['remember'] ?? false);

        if (! Auth::guard('admin')->attempt([
            'email' => (string) $credentials['email'],
            'password' => (string) $credentials['password'],
        ], $remember)) {
            return false;
        }

        $request->session()->regenerate();

        $admin = Auth::guard('admin')->user();

        if ($admin instanceof Admin) {
            $this->adminRolePermissionDefaults->assignDefaultRoleToAdmin($admin);
        }

        return true;
    }

    public function logout(Request $request): void
    {
        Auth::guard('admin')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}

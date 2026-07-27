<?php

use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminPlatformListingController;
use App\Http\Controllers\Admin\AdminRoleManagementController;
use App\Http\Controllers\Admin\AdminSettingsController;
use App\Http\Controllers\Admin\AdminStaffManagementController;
use App\Http\Controllers\Admin\AdminUserManagementController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\CompanyListingController;
use App\Http\Controllers\CompanySetupController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\RoleManagementController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Laravel\Fortify\Http\Controllers\ConfirmablePasswordController;
use Laravel\Fortify\Http\Controllers\ConfirmedPasswordStatusController;
use Laravel\Fortify\Http\Controllers\ConfirmedTwoFactorAuthenticationController;
use Laravel\Fortify\Http\Controllers\EmailVerificationNotificationController;
use Laravel\Fortify\Http\Controllers\EmailVerificationPromptController;
use Laravel\Fortify\Http\Controllers\NewPasswordController;
use Laravel\Fortify\Http\Controllers\PasswordResetLinkController;
use Laravel\Fortify\Http\Controllers\RecoveryCodeController;
use Laravel\Fortify\Http\Controllers\TwoFactorAuthenticatedSessionController;
use Laravel\Fortify\Http\Controllers\TwoFactorAuthenticationController;
use Laravel\Fortify\Http\Controllers\TwoFactorQrCodeController;
use Laravel\Fortify\Http\Controllers\TwoFactorSecretKeyController;
use Laravel\Fortify\Http\Controllers\VerifyEmailController;
use Laravel\Fortify\RoutePath;
use Laravel\Passkeys\Http\Controllers\PasskeyConfirmationController;
use Laravel\Passkeys\Http\Controllers\PasskeyLoginController;
use Laravel\Passkeys\Http\Controllers\PasskeyRegistrationController;

Route::inertia('/', 'Welcome')->name('welcome');

Route::middleware('guest:web,admin')->group(function () {
    Route::get('register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('register', [AuthController::class, 'register'])->name('register.store');

    Route::get('login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AuthController::class, 'login'])->name('login.store');
});

Route::get('profile', [AuthController::class, 'profile'])->name('profile.show');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest:admin,web')->group(function () {
        Route::get('login', [AdminAuthController::class, 'showLogin'])->name('login');
        Route::post('login', [AdminAuthController::class, 'login'])->name('login.store');
    });

    Route::middleware('auth:admin')->group(function () {
        Route::post('logout', [AdminAuthController::class, 'logout'])->name('logout');
        Route::get('settings', [AdminSettingsController::class, 'edit'])->name('settings.edit');
        Route::patch('settings/profile', [AdminSettingsController::class, 'updateProfile'])->name('settings.profile.update');
        Route::put('settings/password', [AdminSettingsController::class, 'updatePassword'])->name('settings.password.update');

        Route::get('dashboard', AdminDashboardController::class)
            ->middleware('admin.permission:admin.dashboard.view')
            ->name('dashboard');

        Route::get('admins', [AdminStaffManagementController::class, 'index'])
            ->middleware('admin.permission:admin.admins.view')
            ->name('admins.index');
        Route::post('admins', [AdminStaffManagementController::class, 'store'])
            ->middleware('admin.permission:admin.admins.manage')
            ->name('admins.store');
        Route::put('admins/{admin}', [AdminStaffManagementController::class, 'update'])
            ->middleware('admin.permission:admin.admins.manage')
            ->name('admins.update');

        Route::get('users', [AdminUserManagementController::class, 'index'])
            ->middleware('admin.permission:admin.users.view')
            ->name('users.index');
        Route::get('users/{user}', [AdminUserManagementController::class, 'show'])
            ->middleware('admin.permission:admin.users.view')
            ->name('users.show');
        Route::put('users/{user}/restriction', [AdminUserManagementController::class, 'updateUserRestriction'])
            ->middleware('admin.permission:admin.users.restrict')
            ->name('users.restriction.update');
        Route::get('companies', [AdminPlatformListingController::class, 'companies'])
            ->middleware('admin.permission:admin.companies.view')
            ->name('companies.index');
        Route::put('companies/{company}/restriction', [AdminPlatformListingController::class, 'updateCompanyRestriction'])
            ->middleware('admin.permission:admin.companies.restrict')
            ->name('companies.restriction.update');
        Route::get('workspaces', [AdminPlatformListingController::class, 'workspaces'])
            ->middleware('admin.permission:admin.workspaces.view')
            ->name('workspaces.index');
        Route::get('workspaces/{workspace}', [AdminUserManagementController::class, 'workspace'])
            ->middleware('admin.permission:admin.workspaces.view')
            ->name('workspaces.show');
        Route::put('workspaces/{workspace}/restriction', [AdminPlatformListingController::class, 'updateWorkspaceRestriction'])
            ->middleware('admin.permission:admin.workspaces.restrict')
            ->name('workspaces.restriction.update');
        Route::get('boards', [AdminPlatformListingController::class, 'boards'])
            ->middleware('admin.permission:admin.boards.view')
            ->name('boards.index');
        Route::get('boards/{board}', [AdminUserManagementController::class, 'board'])
            ->middleware('admin.permission:admin.boards.view')
            ->name('boards.show');
        Route::put('boards/{board}/restriction', [AdminPlatformListingController::class, 'updateBoardRestriction'])
            ->middleware('admin.permission:admin.boards.restrict')
            ->name('boards.restriction.update');
        Route::get('tickets', [AdminPlatformListingController::class, 'cards'])
            ->middleware('admin.permission:admin.cards.view')
            ->name('cards.index');
        Route::put('tickets/{card}/restriction', [AdminPlatformListingController::class, 'updateCardRestriction'])
            ->middleware('admin.permission:admin.cards.restrict')
            ->name('cards.restriction.update');

        Route::get('roles', [AdminRoleManagementController::class, 'index'])
            ->middleware('admin.permission:admin.roles.manage')
            ->name('roles.index');
        Route::post('roles', [AdminRoleManagementController::class, 'store'])
            ->middleware('admin.permission:admin.roles.manage')
            ->name('roles.store');
        Route::get('roles/{adminRole}/permissions', [AdminRoleManagementController::class, 'permissions'])
            ->middleware('admin.permission:admin.roles.manage')
            ->name('roles.permissions.edit');
        Route::put('roles/{adminRole}/permissions', [AdminRoleManagementController::class, 'updatePermissions'])
            ->middleware('admin.permission:admin.roles.manage')
            ->name('roles.permissions.update');
    });
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('company/setup', [CompanySetupController::class, 'create'])->name('company.setup');
    Route::post('company/setup', [CompanySetupController::class, 'store'])->name('company.setup.store');

    Route::get('dashboard', DashboardController::class)
        ->middleware('permission:dashboard.view')
        ->name('dashboard');

    Route::get('companies', [CompanyListingController::class, 'companies'])
        ->middleware('permission:company.view')
        ->name('companies.index');
    Route::get('users', [CompanyListingController::class, 'users'])
        ->middleware('permission:users.view')
        ->name('users.index');
    Route::get('workspaces', [CompanyListingController::class, 'workspaces'])
        ->middleware('permission:workspaces.view')
        ->name('workspaces.index');
    Route::get('boards', [CompanyListingController::class, 'boards'])
        ->middleware('permission:boards.view')
        ->name('boards.index');
    Route::get('tickets', [CompanyListingController::class, 'cards'])
        ->middleware('permission:cards.view')
        ->name('cards.index');

    Route::get('roles', [RoleManagementController::class, 'index'])
        ->middleware('permission:roles.manage')
        ->name('roles.index');
    Route::post('roles', [RoleManagementController::class, 'store'])
        ->middleware('permission:roles.manage')
        ->name('roles.store');
    Route::get('roles/{role}/permissions', [RoleManagementController::class, 'permissions'])
        ->middleware('permission:roles.manage')
        ->name('roles.permissions.edit');
    Route::put('roles/{role}/permissions', [RoleManagementController::class, 'updatePermissions'])
        ->middleware('permission:roles.manage')
        ->name('roles.permissions.update');
});

if (Features::enabled(Features::resetPasswords())) {
    Route::middleware('guest')->group(function () {
        Route::get(RoutePath::for('password.request', '/forgot-password'), [PasswordResetLinkController::class, 'create'])
            ->name('password.request');

        Route::post(RoutePath::for('password.email', '/forgot-password'), [PasswordResetLinkController::class, 'store'])
            ->name('password.email');

        Route::get(RoutePath::for('password.reset', '/reset-password/{token}'), [NewPasswordController::class, 'create'])
            ->name('password.reset');

        Route::post(RoutePath::for('password.update', '/reset-password'), [NewPasswordController::class, 'store'])
            ->name('password.update');
    });
}

if (Features::enabled(Features::emailVerification())) {
    $verificationLimiter = config('fortify.limiters.verification', '6,1');

    Route::middleware('auth')->group(function () use ($verificationLimiter) {
        Route::get(RoutePath::for('verification.notice', '/email/verify'), [EmailVerificationPromptController::class, '__invoke'])
            ->name('verification.notice');

        Route::get(RoutePath::for('verification.verify', '/email/verify/{id}/{hash}'), [VerifyEmailController::class, '__invoke'])
            ->middleware(['signed', 'throttle:'.$verificationLimiter])
            ->name('verification.verify');

        Route::post(RoutePath::for('verification.send', '/email/verification-notification'), [EmailVerificationNotificationController::class, 'store'])
            ->middleware('throttle:'.$verificationLimiter)
            ->name('verification.send');
    });
}

Route::middleware('auth')->group(function () {
    Route::get(RoutePath::for('password.confirm', '/user/confirm-password'), [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::get(RoutePath::for('password.confirmation', '/user/confirmed-password-status'), [ConfirmedPasswordStatusController::class, 'show'])
        ->name('password.confirmation');

    Route::post(RoutePath::for('password.confirm', '/user/confirm-password'), [ConfirmablePasswordController::class, 'store'])
        ->name('password.confirm.store');
});

if (Features::enabled(Features::twoFactorAuthentication())) {
    $twoFactorLimiter = config('fortify.limiters.two-factor');
    $twoFactorThrottle = $twoFactorLimiter ? ['throttle:'.$twoFactorLimiter] : [];
    $twoFactorMiddleware = Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword')
        ? ['auth', 'password.confirm']
        : ['auth'];

    Route::middleware(['guest', ...$twoFactorThrottle])->group(function () {
        Route::get(RoutePath::for('two-factor.login', '/two-factor-challenge'), [TwoFactorAuthenticatedSessionController::class, 'create'])
            ->name('two-factor.login');

        Route::post(RoutePath::for('two-factor.login', '/two-factor-challenge'), [TwoFactorAuthenticatedSessionController::class, 'store'])
            ->name('two-factor.login.store');
    });

    Route::middleware($twoFactorMiddleware)->group(function () {
        Route::post(RoutePath::for('two-factor.enable', '/user/two-factor-authentication'), [TwoFactorAuthenticationController::class, 'store'])
            ->name('two-factor.enable');

        Route::post(RoutePath::for('two-factor.confirm', '/user/confirmed-two-factor-authentication'), [ConfirmedTwoFactorAuthenticationController::class, 'store'])
            ->name('two-factor.confirm');

        Route::delete(RoutePath::for('two-factor.disable', '/user/two-factor-authentication'), [TwoFactorAuthenticationController::class, 'destroy'])
            ->name('two-factor.disable');

        Route::get(RoutePath::for('two-factor.qr-code', '/user/two-factor-qr-code'), [TwoFactorQrCodeController::class, 'show'])
            ->name('two-factor.qr-code');

        Route::get(RoutePath::for('two-factor.secret-key', '/user/two-factor-secret-key'), [TwoFactorSecretKeyController::class, 'show'])
            ->name('two-factor.secret-key');

        Route::get(RoutePath::for('two-factor.recovery-codes', '/user/two-factor-recovery-codes'), [RecoveryCodeController::class, 'index'])
            ->name('two-factor.recovery-codes');

        Route::post(RoutePath::for('two-factor.recovery-codes', '/user/two-factor-recovery-codes'), [RecoveryCodeController::class, 'store'])
            ->name('two-factor.regenerate-recovery-codes');
    });
}

if (Features::enabled(Features::passkeys())) {
    $passkeyLimiter = config('fortify.limiters.passkeys');
    $passkeyThrottle = $passkeyLimiter ? ['throttle:'.$passkeyLimiter] : [];
    $passkeyMiddleware = config('fortify-options.passkeys.confirmPassword', true)
        ? ['auth', 'password.confirm', ...$passkeyThrottle]
        : ['auth', ...$passkeyThrottle];

    Route::middleware(['guest', ...$passkeyThrottle])->group(function () {
        Route::get(RoutePath::for('passkey.login-options', '/passkeys/login/options'), [PasskeyLoginController::class, 'index'])
            ->name('passkey.login-options');

        Route::post(RoutePath::for('passkey.login', '/passkeys/login'), [PasskeyLoginController::class, 'store'])
            ->name('passkey.login');
    });

    Route::middleware(['auth', ...$passkeyThrottle])->group(function () {
        Route::get(RoutePath::for('passkey.confirm-options', '/passkeys/confirm/options'), [PasskeyConfirmationController::class, 'index'])
            ->name('passkey.confirm-options');

        Route::post(RoutePath::for('passkey.confirm', '/passkeys/confirm'), [PasskeyConfirmationController::class, 'store'])
            ->name('passkey.confirm');
    });

    Route::middleware($passkeyMiddleware)->group(function () {
        Route::get(RoutePath::for('passkey.registration-options', '/user/passkeys/options'), [PasskeyRegistrationController::class, 'index'])
            ->name('passkey.registration-options');

        Route::post(RoutePath::for('passkey.store', '/user/passkeys'), [PasskeyRegistrationController::class, 'store'])
            ->name('passkey.store');

        Route::delete(RoutePath::for('passkey.destroy', '/user/passkeys/{passkey}'), [PasskeyRegistrationController::class, 'destroy'])
            ->name('passkey.destroy');
    });
}

require __DIR__.'/settings.php';

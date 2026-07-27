<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminPasswordUpdateRequest;
use App\Http\Requests\Admin\AdminProfileUpdateRequest;
use App\Models\Admin;
use App\Repositories\Contracts\AdminSettingsRepositoryInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminSettingsController extends Controller
{
    public function __construct(
        private readonly AdminSettingsRepositoryInterface $adminSettingsRepository,
    ) {}

    public function edit(Request $request): Response
    {
        return Inertia::render('Admin/Settings/Index', $this->adminSettingsRepository->payload($this->admin($request)));
    }

    public function updateProfile(AdminProfileUpdateRequest $request): RedirectResponse
    {
        $this->adminSettingsRepository->updateProfile($this->admin($request), $request->profileData());

        return back();
    }

    public function updatePassword(AdminPasswordUpdateRequest $request): RedirectResponse
    {
        $this->adminSettingsRepository->updatePassword($this->admin($request), $request->newPassword());

        return back();
    }

    private function admin(Request $request): Admin
    {
        $admin = $request->user('admin');

        abort_unless($admin instanceof Admin, 403);

        return $admin;
    }
}

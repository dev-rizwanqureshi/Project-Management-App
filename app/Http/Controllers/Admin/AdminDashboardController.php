<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Repositories\Contracts\AdminDashboardRepositoryInterface;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminDashboardController extends Controller
{
    public function __construct(
        private readonly AdminDashboardRepositoryInterface $adminDashboardRepository,
    ) {}

    public function __invoke(Request $request): Response
    {
        $admin = $request->user('admin');

        abort_unless($admin instanceof Admin, 403);

        return Inertia::render('Admin/Dashboard', $this->adminDashboardRepository->payload($admin));
    }
}

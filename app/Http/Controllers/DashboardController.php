<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Repositories\Contracts\DashboardRepositoryInterface;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardRepositoryInterface $dashboardRepository,
    ) {}

    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        abort_unless($user instanceof User, 403);

        return Inertia::render('Dashboard', $this->dashboardRepository->payload($user));
    }
}

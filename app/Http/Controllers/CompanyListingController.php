<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Repositories\Contracts\CompanyListingRepositoryInterface;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CompanyListingController extends Controller
{
    public function __construct(
        private readonly CompanyListingRepositoryInterface $companyListingRepository,
    ) {}

    public function companies(Request $request): Response
    {
        return Inertia::render('Companies/Index', $this->companyListingRepository->companies($this->user($request), $request));
    }

    public function users(Request $request): Response
    {
        return Inertia::render('Users/Index', $this->companyListingRepository->users($this->user($request), $request));
    }

    public function workspaces(Request $request): Response
    {
        return Inertia::render('Workspaces/Index', $this->companyListingRepository->workspaces($this->user($request), $request));
    }

    public function boards(Request $request): Response
    {
        return Inertia::render('Boards/Index', $this->companyListingRepository->boards($this->user($request), $request));
    }

    public function cards(Request $request): Response
    {
        return Inertia::render('Tickets/Index', $this->companyListingRepository->cards($this->user($request), $request));
    }

    private function user(Request $request): User
    {
        $user = $request->user();

        abort_unless($user instanceof User, 403);

        return $user;
    }
}

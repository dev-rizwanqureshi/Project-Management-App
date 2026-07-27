<?php

namespace App\Http\Controllers;

use App\Http\Requests\Company\StoreCompanySetupRequest;
use App\Models\User;
use App\Repositories\Contracts\CompanySetupRepositoryInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CompanySetupController extends Controller
{
    public function __construct(
        private readonly CompanySetupRepositoryInterface $companySetupRepository,
    ) {}

    public function create(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        abort_unless($user instanceof User, 403);

        if ($this->companySetupRepository->userHasActiveCompany($user)) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('Company/Setup');
    }

    public function store(StoreCompanySetupRequest $request): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user instanceof User, 403);

        $this->companySetupRepository->createForUser($user, $request->validated());

        return redirect()
            ->route('dashboard')
            ->with('toast', [
                'type' => 'success',
                'message' => 'Company created successfully.',
            ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterInvitationRequest;
use App\Http\Requests\StoreInvitationRequest;
use App\Models\Board;
use App\Models\Company;
use App\Models\Invitation;
use App\Models\User;
use App\Models\Workspace;
use App\Services\InvitationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class InvitationController extends Controller
{
    public function store(StoreInvitationRequest $request, InvitationService $invitationService): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->company_id, 403);

        $validated = $request->validated();
        $company = Company::query()->whereKey($user->company_id)->firstOrFail();
        Gate::forUser($user)->authorize('create', [Invitation::class, $company]);

        $scope = (string) $validated['scope'];
        $workspace = null;
        $board = null;

        if ($scope !== 'company') {
            $workspace = Workspace::query()
                ->whereKey((int) $validated['workspace_id'])
                ->where('company_id', $company->id)
                ->where('is_restricted', false)
                ->firstOrFail();
        }

        if ($scope === 'board') {
            $board = Board::query()
                ->whereKey((int) $validated['board_id'])
                ->where('workspace_id', $workspace?->id)
                ->where('is_restricted', false)
                ->where('is_archived', false)
                ->firstOrFail();
        }

        $invitationService->create(
            $user,
            $company,
            (string) $validated['email'],
            $scope,
            $workspace,
            $board,
            (string) $validated['role'],
        );

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Invitation sent successfully.',
        ]);
    }

    public function show(string $token, Request $request, InvitationService $invitationService): Response
    {
        $invitation = $invitationService->findPending($token);
        $user = $request->user();

        return Inertia::render('Invitations/Show', [
            'invitation' => [
                'token' => $token,
                'email' => $invitation->email,
                'role' => $invitation->role,
                'role_label' => $invitation->role === 'guest' ? 'Viewer' : ucfirst($invitation->role),
                'scope' => $invitation->scopeLabel(),
                'company' => $invitation->company->only(['id', 'name']),
                'workspace' => $invitation->workspace?->only(['id', 'name']),
                'board' => $invitation->board?->only(['id', 'name']),
                'expires_at' => $invitation->expires_at->toISOString(),
            ],
            'authenticated' => $user instanceof User,
            'email_matches' => $user instanceof User
                && mb_strtolower($user->email) === mb_strtolower($invitation->email),
            'existing_account' => User::query()
                ->whereRaw('LOWER(email) = ?', [mb_strtolower($invitation->email)])
                ->exists(),
        ]);
    }

    public function accept(string $token, Request $request, InvitationService $invitationService): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $invitation = $invitationService->findPending($token);
        $invitationService->accept($invitation, $user);

        return redirect()->route('dashboard')->with('toast', [
            'type' => 'success',
            'message' => 'You joined the invitation successfully.',
        ]);
    }

    public function register(
        string $token,
        RegisterInvitationRequest $request,
        InvitationService $invitationService,
    ): RedirectResponse {
        $invitation = $invitationService->findPending($token);

        if (User::query()->whereRaw('LOWER(email) = ?', [mb_strtolower($invitation->email)])->exists()) {
            return redirect()->route('login', ['invitation' => $token]);
        }

        $user = $invitationService->registerAndAccept(
            $invitation,
            (string) $request->validated('name'),
            (string) $request->validated('password'),
        );

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('toast', [
            'type' => 'success',
            'message' => 'Your account was created and the invitation was accepted.',
        ]);
    }
}

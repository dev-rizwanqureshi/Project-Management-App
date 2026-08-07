<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterCompanyRequest;
use App\Http\Resources\UserResource;
use App\Services\Contracts\AuthServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthServiceInterface $authService,
    ) {}

    public function showRegister(): Response
    {
        return Inertia::render('auth/Register');
    }

    public function register(RegisterCompanyRequest $request): JsonResponse
    {
        try {
            $registered = $this->authService->registerCompany($request->validated());
            $userData = UserResource::make($registered['user'])->resolve($request);

            return response()->json([
                'user' => $userData,
                'company' => $userData['company'],
            ], 201);
        } catch (Throwable $exception) {
            Log::error('Company registration failed.', [
                'exception' => $exception,
            ]);

            return response()->json([
                'message' => 'Registration failed',
            ], 500);
        }
    }

    public function showLogin(Request $request): Response
    {
        return Inertia::render('auth/Login', [
            'invitationToken' => $request->query('invitation'),
        ]);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        if (! $this->authService->login($request->validated(), $request)) {
            return response()->json([
                'message' => 'Invalid credentials',
            ], 422);
        }

        return response()->json([
            'message' => 'Logged in successfully',
        ]);
    }

    public function logout(Request $request): JsonResponse|RedirectResponse
    {
        $this->authService->logout($request);

        if ($request->header('X-Inertia')) {
            return redirect()->route('login');
        }

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }
}

<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $this->get(route('register'))->assertOk();
    }

    public function test_user_can_register_with_valid_data_and_company_is_created(): void
    {
        $response = $this->postJson(route('register.store'), [
            'company_name' => 'Acme Projects',
            'company_email' => 'hello@acme.test',
            'name' => 'Test Owner',
            'email' => 'owner@acme.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('user.name', 'Test Owner')
            ->assertJsonPath('user.email', 'owner@acme.test')
            ->assertJsonPath('user.role', 'owner')
            ->assertJsonPath('user.company.name', 'Acme Projects')
            ->assertJsonPath('company.name', 'Acme Projects');

        $this->assertDatabaseHas('companies', [
            'name' => 'Acme Projects',
            'email' => 'hello@acme.test',
            'slug' => 'acme-projects',
        ]);

        $this->assertDatabaseHas('users', [
            'name' => 'Test Owner',
            'email' => 'owner@acme.test',
            'role' => 'owner',
        ]);

        $user = User::query()->where('email', 'owner@acme.test')->firstOrFail();

        $this->assertDatabaseHas('company_user', [
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'role' => 'owner',
            'status' => 'active',
            'left_at' => null,
        ]);

        $this->assertAuthenticatedAs($user);
    }

    public function test_registration_fails_with_validation_errors_when_required_fields_are_missing(): void
    {
        $this->postJson(route('register.store'), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'company_name',
                'company_email',
                'name',
                'email',
                'password',
                'password_confirmation',
            ]);
    }

    public function test_registration_fails_when_email_is_already_taken(): void
    {
        $user = User::factory()->create([
            'email' => 'taken@example.test',
        ]);

        $this->postJson(route('register.store'), [
            'company_name' => 'Acme Projects',
            'company_email' => 'hello@acme.test',
            'name' => 'Test Owner',
            'email' => $user->email,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }
}

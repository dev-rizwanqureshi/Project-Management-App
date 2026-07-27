<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CompanySetupTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_without_company_is_redirected_from_dashboard_to_company_setup(): void
    {
        $user = $this->userWithoutCompany();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('company.setup'));
    }

    public function test_user_without_company_can_open_company_setup_page(): void
    {
        $user = $this->userWithoutCompany();

        $this->actingAs($user)
            ->get(route('company.setup'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Company/Setup')
            );
    }

    public function test_user_with_company_is_redirected_from_company_setup_to_dashboard(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('company.setup'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_user_without_company_can_create_company_and_become_owner(): void
    {
        $user = $this->userWithoutCompany();

        $this->actingAs($user)
            ->post(route('company.setup.store'), [
                'name' => 'Acme Projects',
                'email' => 'hello@acme.test',
                'phone' => '+1 555 123 4567',
                'website' => 'https://acme.test',
                'industry' => 'Project management',
                'team_size' => '11-50',
                'address_line' => '100 Product Avenue',
                'city' => 'Austin',
                'state' => 'Texas',
                'country' => 'United States',
                'postal_code' => '78701',
                'timezone' => 'America/Chicago',
                'description' => 'Internal project management workspace.',
            ])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('toast.message', 'Company created successfully.');

        $company = Company::query()->where('slug', 'acme-projects')->firstOrFail();

        $this->assertSame($company->id, $user->refresh()->company_id);
        $this->assertSame('owner', $user->role);
        $this->assertNotNull($user->role_id);

        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'name' => 'Acme Projects',
            'email' => 'hello@acme.test',
            'website' => 'https://acme.test',
            'industry' => 'Project management',
        ]);

        $this->assertDatabaseHas('company_user', [
            'company_id' => $company->id,
            'user_id' => $user->id,
            'role' => 'owner',
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('roles', [
            'company_id' => $company->id,
            'slug' => 'owner',
        ]);
    }

    public function test_company_setup_requires_company_name_and_email(): void
    {
        $user = $this->userWithoutCompany();

        $this->actingAs($user)
            ->post(route('company.setup.store'), [
                'name' => '',
                'email' => 'not-an-email',
            ])
            ->assertSessionHasErrors(['name', 'email']);
    }

    private function userWithoutCompany(): User
    {
        return User::factory()->create([
            'company_id' => null,
            'role' => 'member',
            'role_id' => null,
        ]);
    }
}

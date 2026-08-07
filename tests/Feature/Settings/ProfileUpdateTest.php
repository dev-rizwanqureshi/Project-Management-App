<?php

namespace Tests\Feature\Settings;

use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('profile.edit'));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('canUpdateCompany', true)
            );
    }

    public function test_profile_information_can_be_updated()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch(route('profile.update'), [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit'));

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch(route('profile.update'), [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit'));

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_company_owner_can_update_company_details(): void
    {
        $company = Company::factory()->create([
            'name' => 'Old Company',
            'email' => 'old@example.com',
        ]);
        $owner = User::factory()->for($company)->create(['role' => 'owner']);

        $response = $this
            ->actingAs($owner)
            ->patch(route('company.profile.update'), [
                'name' => 'Riraa Studio',
                'email' => 'hello@riraa.test',
                'phone' => '+1 555 123 4567',
                'website' => 'https://riraa.test',
                'industry' => 'Project management',
                'team_size' => '11-50',
                'address_line' => '100 Product Avenue',
                'city' => 'Austin',
                'state' => 'Texas',
                'country' => 'United States',
                'postal_code' => '78701',
                'timezone' => 'America/Chicago',
                'description' => 'Riraa studio company profile.',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit'));

        $company->refresh();

        $this->assertSame('Riraa Studio', $company->name);
        $this->assertSame('hello@riraa.test', $company->email);
        $this->assertSame('+1 555 123 4567', $company->phone);
        $this->assertSame('https://riraa.test', $company->website);
        $this->assertSame('Project management', $company->industry);
        $this->assertSame('11-50', $company->team_size);
        $this->assertSame('Austin', $company->city);
    }

    public function test_company_member_cannot_update_company_details(): void
    {
        $company = Company::factory()->create([
            'name' => 'Original Company',
            'email' => 'original@example.com',
        ]);
        $member = User::factory()->for($company)->create(['role' => 'member']);

        $this
            ->actingAs($member)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('canUpdateCompany', false)
            );

        $this
            ->actingAs($member)
            ->patch(route('company.profile.update'), [
                'name' => 'Changed Company',
                'email' => 'changed@example.com',
            ])
            ->assertForbidden();

        $company->refresh();

        $this->assertSame('Original Company', $company->name);
        $this->assertSame('original@example.com', $company->email);
    }

    public function test_user_can_delete_their_account()
    {
        $user = User::factory()->create(['role' => 'member']);
        $secondCompany = Company::factory()->create();
        CompanyUser::query()->create([
            'company_id' => $secondCompany->id,
            'user_id' => $user->id,
            'role' => 'member',
            'status' => 'active',
            'is_company_wide' => true,
            'joined_at' => now(),
        ]);

        $response = $this
            ->actingAs($user)
            ->delete(route('profile.destroy'), [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('welcome'));

        $this->assertGuest();
        $this->assertSoftDeleted($user);
        $this->assertDatabaseHas('company_user', [
            'user_id' => $user->id,
            'status' => 'left',
        ]);
        $this->assertDatabaseCount('company_user', 2);
        $this->assertDatabaseMissing('company_user', [
            'user_id' => $user->id,
            'status' => 'active',
        ]);
    }

    public function test_last_company_owner_cannot_delete_their_account(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);

        $this->actingAs($owner)
            ->from(route('profile.edit'))
            ->delete(route('profile.destroy'), [
                'password' => 'password',
            ])
            ->assertSessionHasErrors('password')
            ->assertRedirect(route('profile.edit'));

        $this->assertNotSoftDeleted('users', ['id' => $owner->id]);
        $this->assertDatabaseHas('company_user', [
            'user_id' => $owner->id,
            'role' => 'owner',
            'status' => 'active',
        ]);
    }

    public function test_correct_password_must_be_provided_to_delete_account()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from(route('profile.edit'))
            ->delete(route('profile.destroy'), [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrors('password')
            ->assertRedirect(route('profile.edit'));

        $this->assertNotNull($user->fresh());
    }
}

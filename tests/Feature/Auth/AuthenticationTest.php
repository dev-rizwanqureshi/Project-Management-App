<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_for_authenticated_pages(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }
}

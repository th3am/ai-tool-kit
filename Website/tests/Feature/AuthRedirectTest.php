<?php

namespace Tests\Feature;

use Tests\TestCase;

class AuthRedirectTest extends TestCase
{
    public function test_guest_visiting_dashboard_is_redirected_to_login_with_flash_message(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('auth_required', 'You need to log in first to access the dashboard.');
    }

    public function test_login_page_triggers_auth_required_toast_after_dashboard_redirect(): void
    {
        $response = $this->followingRedirects()->get('/dashboard');

        $response->assertOk();
        $response->assertSee('You need to log in first to access the dashboard.');
        $response->assertSee('window.showToast', false);
        $response->assertDontSee('border-amber-200', false);
    }
}

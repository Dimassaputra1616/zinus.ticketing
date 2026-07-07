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
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register_and_wait_for_admin_approval(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@zinus.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertGuest();
        $response
            ->assertRedirect(route('registration.pending'))
            ->assertSessionHas('registered_email', 'test@zinus.com');

        $this->assertDatabaseHas('users', [
            'email' => 'test@zinus.com',
            'approval_status' => User::APPROVAL_PENDING,
        ]);
    }

    public function test_registration_requires_company_email_domain(): void
    {
        config(['company.external_email_allowlist' => []]);

        $response = $this->post('/register', [
            'name' => 'External User',
            'email' => 'external@gmail.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_registration_allows_approved_external_admin_email_but_still_requires_approval(): void
    {
        config(['company.external_email_allowlist' => ['dimassputra1616@gmail.com']]);

        $response = $this->post('/register', [
            'name' => 'Dimas Saputra',
            'email' => 'dimassputra1616@gmail.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertGuest();
        $response->assertRedirect(route('registration.pending'));
        $this->assertDatabaseHas('users', [
            'email' => 'dimassputra1616@gmail.com',
            'approval_status' => User::APPROVAL_PENDING,
        ]);
    }
}

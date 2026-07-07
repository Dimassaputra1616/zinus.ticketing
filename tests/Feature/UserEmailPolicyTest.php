<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserEmailPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_user_with_company_email_domain(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_admin' => true]);

        $this->actingAs($admin)
            ->post(route('users.store'), [
                'name' => 'Zinus Staff',
                'email' => 'staff@zinus.com',
                'password' => 'password',
                'password_confirmation' => 'password',
                'role' => 'user',
            ])
            ->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', ['email' => 'staff@zinus.com']);
        $this->assertDatabaseHas('users', [
            'email' => 'staff@zinus.com',
            'approval_status' => User::APPROVAL_APPROVED,
            'approved_by' => $admin->id,
        ]);
    }

    public function test_admin_cannot_create_user_with_unapproved_personal_email(): void
    {
        config(['company.external_email_allowlist' => []]);

        $admin = User::factory()->create(['role' => 'admin', 'is_admin' => true]);

        $this->actingAs($admin)
            ->post(route('users.store'), [
                'name' => 'Personal Email User',
                'email' => 'personal@gmail.com',
                'password' => 'password',
                'password_confirmation' => 'password',
                'role' => 'user',
            ])
            ->assertSessionHasErrors('email');

        $this->assertDatabaseMissing('users', ['email' => 'personal@gmail.com']);
    }
}

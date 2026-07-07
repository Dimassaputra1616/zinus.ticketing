<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_approve_pending_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_admin' => true]);
        $pendingUser = User::factory()->create([
            'approval_status' => User::APPROVAL_PENDING,
            'approved_at' => null,
        ]);

        $this->actingAs($admin)
            ->post(route('users.approve', $pendingUser))
            ->assertRedirect(route('users.index'));

        $pendingUser->refresh();

        $this->assertSame(User::APPROVAL_APPROVED, $pendingUser->approval_status);
        $this->assertSame($admin->id, $pendingUser->approved_by);
        $this->assertNotNull($pendingUser->approved_at);
        $this->assertNull($pendingUser->rejected_at);
    }

    public function test_admin_can_reject_pending_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_admin' => true]);
        $pendingUser = User::factory()->create([
            'approval_status' => User::APPROVAL_PENDING,
            'approved_at' => null,
        ]);

        $this->actingAs($admin)
            ->post(route('users.reject', $pendingUser))
            ->assertRedirect(route('users.index'));

        $pendingUser->refresh();

        $this->assertSame(User::APPROVAL_REJECTED, $pendingUser->approval_status);
        $this->assertSame($admin->id, $pendingUser->rejected_by);
        $this->assertNotNull($pendingUser->rejected_at);
    }

    public function test_user_list_shows_pending_accounts_first(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_admin' => true]);
        $approvedUser = User::factory()->create([
            'name' => 'Approved User',
            'approval_status' => User::APPROVAL_APPROVED,
        ]);
        $pendingUser = User::factory()->create([
            'name' => 'Pending User',
            'approval_status' => User::APPROVAL_PENDING,
            'approved_at' => null,
        ]);

        $response = $this->actingAs($admin)->get(route('users.index'));

        $response
            ->assertOk()
            ->assertSee('Menunggu Approval')
            ->assertSeeInOrder([$pendingUser->name, $approvedUser->name]);
    }
}

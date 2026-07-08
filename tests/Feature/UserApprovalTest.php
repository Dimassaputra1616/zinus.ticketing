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

    public function test_approval_actions_are_hidden_for_approved_accounts(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_admin' => true]);
        User::factory()->create([
            'name' => 'Approved User',
            'approval_status' => User::APPROVAL_APPROVED,
        ]);

        $this->actingAs($admin)
            ->get(route('users.index'))
            ->assertOk()
            ->assertDontSee('Reject');
    }

    public function test_pending_account_row_focuses_on_approval_actions(): void
    {
        $superAdmin = User::factory()->create([
            'role' => 'admin',
            'is_admin' => true,
            'is_super_admin' => true,
        ]);
        User::factory()->create([
            'name' => 'Pending Review User',
            'approval_status' => User::APPROVAL_PENDING,
            'approved_at' => null,
        ]);

        $response = $this->actingAs($superAdmin)
            ->getJson(route('users.index', [
                'fragment' => 1,
                'q' => 'Pending Review User',
            ]))
            ->assertOk();

        $table = $response->json('table');

        $this->assertStringContainsString('Pending Review User', $table);
        $this->assertStringContainsString('Menunggu Approval', $table);
        $this->assertStringContainsString('whitespace-nowrap', $table);
        $this->assertStringContainsString('Approve', $table);
        $this->assertStringContainsString('Reject', $table);
        $this->assertStringContainsString('Edit', $table);
        $this->assertStringContainsString('Hapus', $table);
        $this->assertStringNotContainsString('Reset PW', $table);
        $this->assertStringNotContainsString('Reset Password', $table);
    }

    public function test_admin_cannot_approve_non_pending_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_admin' => true]);
        $approvedUser = User::factory()->create([
            'approval_status' => User::APPROVAL_APPROVED,
            'approved_by' => null,
        ]);

        $this->actingAs($admin)
            ->postJson(route('users.approve', $approvedUser))
            ->assertStatus(422)
            ->assertJson([
                'message' => 'Approval hanya tersedia untuk akun baru yang masih menunggu approval.',
            ]);

        $approvedUser->refresh();

        $this->assertSame(User::APPROVAL_APPROVED, $approvedUser->approval_status);
        $this->assertNull($approvedUser->approved_by);
    }

    public function test_admin_cannot_reject_non_pending_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_admin' => true]);
        $approvedUser = User::factory()->create([
            'approval_status' => User::APPROVAL_APPROVED,
            'rejected_at' => null,
            'rejected_by' => null,
        ]);

        $this->actingAs($admin)
            ->postJson(route('users.reject', $approvedUser))
            ->assertStatus(422)
            ->assertJson([
                'message' => 'Reject hanya tersedia untuk akun baru yang masih menunggu approval.',
            ]);

        $approvedUser->refresh();

        $this->assertSame(User::APPROVAL_APPROVED, $approvedUser->approval_status);
        $this->assertNull($approvedUser->rejected_at);
        $this->assertNull($approvedUser->rejected_by);
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

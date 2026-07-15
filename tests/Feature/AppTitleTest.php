<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppTitleTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_uses_home_browser_title(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('<title>Home - ', false);
    }

    public function test_ticket_index_uses_page_browser_title(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_admin' => true]);

        $this->actingAs($admin)
            ->get(route('tickets.index'))
            ->assertOk()
            ->assertSee('<title>Ticket List - ', false);
    }
}

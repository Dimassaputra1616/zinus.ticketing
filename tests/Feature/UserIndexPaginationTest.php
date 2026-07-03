<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserIndexPaginationTest extends TestCase
{
    use RefreshDatabase;

    public function test_direct_fragment_url_redirects_to_the_full_users_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_admin' => true]);

        $this->actingAs($admin)
            ->get(route('users.index', ['fragment' => 1, 'page' => 5]))
            ->assertRedirect(route('users.index', ['page' => 5]));
    }

    public function test_json_fragment_pagination_does_not_leak_fragment_parameter_into_links(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_admin' => true]);
        User::factory()->count(25)->create();

        $response = $this->actingAs($admin)
            ->getJson(route('users.index', ['fragment' => 1, 'page' => 2]))
            ->assertOk()
            ->assertJsonStructure(['table']);

        $table = $response->json('table');

        $this->assertStringContainsString('page=3', $table);
        $this->assertStringNotContainsString('fragment=1', $table);
    }

    public function test_full_users_page_pagination_links_never_contain_fragment_parameter(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_admin' => true]);
        User::factory()->count(15)->create();

        $this->actingAs($admin)
            ->get(route('users.index'))
            ->assertOk()
            ->assertSee('page=2', false)
            ->assertDontSee('fragment=1', false);
    }
}

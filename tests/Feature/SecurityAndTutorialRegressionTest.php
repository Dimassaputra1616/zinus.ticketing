<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Tutorial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityAndTutorialRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_tutorial_preview_renders_and_escapes_tutorial_content(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_admin' => true,
        ]);
        $category = Category::create(['name' => 'Security']);
        $tutorial = Tutorial::create([
            'title' => 'Preview Draft Tutorial',
            'slug' => 'preview-draft-tutorial',
            'content' => '<script>alert(1)</script>',
            'category_id' => $category->id,
            'user_id' => $admin->id,
            'is_active' => false,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.tutorials.show', $tutorial))
            ->assertOk()
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_public_database_fix_route_is_not_registered(): void
    {
        $this->get('/api/fix-db')->assertNotFound();
    }
}

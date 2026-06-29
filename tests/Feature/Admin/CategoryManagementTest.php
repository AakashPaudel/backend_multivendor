<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CategoryManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_can_crud_categories_and_public_endpoints_only_show_active_categories(): void
    {
        Storage::fake('public');

        $admin = User::factory()->admin()->create();

        Sanctum::actingAs($admin);

        $createResponse = $this->withHeader('Accept', 'application/json')->post('/api/admin/categories', [
            'name' => 'Electronics',
            'description' => 'Devices and gadgets',
            'image' => UploadedFile::fake()->image('category.jpg'),
            'is_active' => true,
        ]);

        $categoryId = $createResponse->json('id');

        $createResponse
            ->assertOk()
            ->assertJsonPath('name', 'Electronics')
            ->assertJsonPath('slug', 'electronics')
            ->assertJsonPath('is_active', true);

        $category = Category::findOrFail($categoryId);
        Storage::disk('public')->assertExists($category->image_path);

        $duplicateResponse = $this->postJson('/api/admin/categories', [
            'name' => 'Electronics',
        ]);

        $duplicateResponse
            ->assertOk()
            ->assertJsonPath('slug', 'electronics-2');

        $this->putJson("/api/admin/categories/{$category->id}", [
            'name' => 'Updated Electronics',
            'is_active' => false,
        ])
            ->assertOk()
            ->assertJsonPath('slug', 'updated-electronics')
            ->assertJsonPath('is_active', false);

        $this->getJson('/api/categories')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->getJson('/api/categories/electronics-2')
            ->assertOk()
            ->assertJsonPath('slug', 'electronics-2');

        $this->getJson('/api/categories/updated-electronics')
            ->assertNotFound();

        $this->deleteJson("/api/admin/categories/{$category->id}")
            ->assertNoContent();
    }

    public function test_non_admin_cannot_manage_categories(): void
    {
        $vendor = User::factory()->vendor()->create();
        Sanctum::actingAs($vendor);

        $this->withHeader('Accept', 'application/json')->post('/api/admin/categories', [
            'name' => 'Forbidden Category',
        ])
            ->assertForbidden()
            ->assertJson([
                'message' => 'This action is unauthorized.',
            ]);
    }
}

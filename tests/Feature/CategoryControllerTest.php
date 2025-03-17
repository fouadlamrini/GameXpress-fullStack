<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CategoryControllerTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->seed(RolesPermissionsSeeder::class);

        // Create user and assign appropriate role
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        // Authenticate the user for all tests
        Sanctum::actingAs($user, ['*']);
    }

    public function test_getting_all_categories()
    {
        // Create some categories
        Category::factory()->count(3)->create();
        $response = $this->getJson(route('categories.index'));
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'categories' => [
                    '*' => [
                        'id',
                        'name',
                        'slug',
                        'icon_path',
                        'created_at',
                        'updated_at'
                    ]
                ]
            ]);
    }

    public function test_can_store_a_category()
    {
        Storage::fake('public');

        $data = [
            'name' => 'Test Category',
            'icon_path' => \Illuminate\Http\UploadedFile::fake()->image('category-icon.jpg')
        ];

        $response = $this->postJson(route('categories.store'), $data);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'category' => [
                    'id',
                    'name',
                    'slug',
                    'icon_path',
                    'created_at',
                    'updated_at'
                ]
            ]);

        // Verify category is in the database
        $this->assertDatabaseHas('categories', [
            'name' => 'Test Category',
            'slug' => 'test-category'
        ]);

        // Get the created category
        $category = Category::where('name', 'Test Category')->first();

        // Verify icon exists in storage
        Storage::disk('public')->assertExists($category->icon_path);
    }

    public function test_can_store_a_category_without_icon()
    {
        $data = [
            'name' => 'Test Category No Icon'
        ];

        $response = $this->postJson(route('categories.store'), $data);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'category' => [
                    'id',
                    'name',
                    'slug',
                    'created_at',
                    'updated_at'
                ]
            ]);

        // Verify category is in the database
        $this->assertDatabaseHas('categories', [
            'name' => 'Test Category No Icon',
            'slug' => 'test-category-no-icon',
            'icon_path' => null
        ]);
    }

    public function test_category_validation_on_store()
    {
        // Test empty name
        $response = $this->postJson(route('categories.store'), [
            'name' => ''
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);

        // Test duplicate name
        Category::create([
            'name' => 'Existing Category',
            'slug' => 'existing-category'
        ]);

        $response = $this->postJson(route('categories.store'), [
            'name' => 'Existing Category'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);

        // Test invalid icon
        $response = $this->postJson(route('categories.store'), [
            'name' => 'Valid Name',
            'icon_path' => \Illuminate\Http\UploadedFile::fake()->create('document.pdf', 100)
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['icon_path']);
    }

    public function test_can_update_a_category()
    {
        Storage::fake('public');

        // Create a category
        $category = Category::create([
            'name' => 'Original Category',
            'slug' => 'original-category'
        ]);

        $data = [
            'name' => 'Updated Category',
            'icon_path' => \Illuminate\Http\UploadedFile::fake()->image('new-icon.jpg')
        ];

        $response = $this->putJson(route('categories.update', $category->id), $data);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'category' => [
                    'id',
                    'name',
                    'slug',
                    'icon_path',
                    'created_at',
                    'updated_at'
                ]
            ])
            ->assertJson([
                'category' => [
                    'name' => 'Updated Category',
                    'slug' => 'updated-category'
                ]
            ]);

        // Verify category is updated in the database
        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Updated Category',
            'slug' => 'updated-category'
        ]);

        // Refresh the model
        $category->refresh();

        // Verify icon exists in storage
        Storage::disk('public')->assertExists($category->icon_path);
    }

    public function test_can_update_a_category_without_changing_icon()
    {
        // Create a category with an icon
        $category = Category::create([
            'name' => 'Original Category',
            'slug' => 'original-category',
            'icon_path' => 'original/path.jpg'
        ]);

        $data = [
            'name' => 'Updated Category'
        ];

        $response = $this->putJson(route('categories.update', $category->id), $data);

        $response->assertStatus(200);

        // Verify category is updated in the database but icon remains the same
        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Updated Category',
            'slug' => 'updated-category',
            'icon_path' => 'original/path.jpg'
        ]);
    }

    public function test_category_validation_on_update()
    {
        // Create categories
        $category = Category::create([
            'name' => 'My Category',
            'slug' => 'my-category'
        ]);

        Category::create([
            'name' => 'Another Category',
            'slug' => 'another-category'
        ]);

        // Test empty name
        $response = $this->putJson(route('categories.update', $category->id), [
            'name' => ''
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);

        // Test duplicate name with another existing category
        $response = $this->putJson(route('categories.update', $category->id), [
            'name' => 'Another Category'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);

        // Test invalid icon
        $response = $this->putJson(route('categories.update', $category->id), [
            'name' => 'Valid Update Name',
            'icon_path' => \Illuminate\Http\UploadedFile::fake()->create('document.pdf', 100)
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['icon_path']);
    }

    public function test_can_delete_a_category()
    {
        Storage::fake('public');

        // Create a category with a real file in storage
        $iconPath = 'categories/test-icon.jpg';
        Storage::disk('public')->put($iconPath, 'test contents');

        $category = Category::create([
            'name' => 'Delete Test Category',
            'slug' => 'delete-test-category',
            'icon_path' => $iconPath
        ]);

        $response = $this->deleteJson(route('categories.destroy', $category->id));

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'category deleted successfully'
            ]);

        // Verify category is deleted from the database
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);

        // Verify icon is deleted from storage
        Storage::disk('public')->assertMissing($iconPath);
    }

    public function test_can_delete_a_category_without_icon()
    {
        // Create a category without an icon
        $category = Category::create([
            'name' => 'No Icon Category',
            'slug' => 'no-icon-category'
        ]);

        $response = $this->deleteJson(route('categories.destroy', $category->id));

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'category deleted successfully'
            ]);

        // Verify category is deleted from the database
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_cannot_delete_nonexistent_category()
    {
        $response = $this->deleteJson(route('categories.destroy', 999));

        $response->assertStatus(404);
    }
}

<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;


class ProductControllerTest extends TestCase
{
    use RefreshDatabase;
    public function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->seed(RoleAndPermissionSeeder::class);

        // Create user and assign 'product_manager' role
        $user = User::factory()->create();
        $user->assignRole('product_manager');

        // Authenticate the user for all tests
        Sanctum::actingAs($user, ['*']);
    }

    // show all products
    public function test_getting_all_products()
    {
        $response = $this->getJson(route('products.index'));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'products' => [
                    '*' => [
                        'id',
                        'name',
                        'slug',
                        'price',
                        'stock',
                        'category_id',
                        'status',
                        'images' => [
                            '*' => [
                                'id',
                                'image_path',
                                'is_primary',
                                'product_id',
                                'created_at',
                                'updated_at'
                            ]
                        ],
                        'created_at',
                        'updated_at'
                    ]
                ]
            ]);
    }

    // test products
    public function test_can_store_a_product()
    {
        // Fake file storage
        Storage::fake('public');
        $category = Category::factory()->create();

        // Prepare product data
        $data = [
            'name' => 'Test Product13',
            'price' => 99.99,
            'stock' => 10,
            'category_id' => $category->id,
            'primary_image' => \Illuminate\Http\UploadedFile::fake()->image('primary.jpg'),
            'images' => [
                \Illuminate\Http\UploadedFile::fake()->image('image1.jpg'),
                \Illuminate\Http\UploadedFile::fake()->image('image2.jpg'),
            ],
        ];

        // Make POST request to store the product
        $response = $this->postJson(route('products.store'), $data);

        // Assert successful response and structure
        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'message',
                'product' => [
                    'id',
                    'name',
                    'slug',
                    'price',
                    'stock',
                    'category_id',
                    'status',
                    'images' => [
                        '*' => [
                            'id',
                            'product_id',
                            'image_path',
                            'is_primary',
                            'created_at',
                            'updated_at'
                        ]
                    ],
                    'created_at',
                    'updated_at'
                ]
            ]);

        // Verify product is in the database
        $this->assertDatabaseHas('products', [
            'name' => 'Test Product13',
            'slug' => 'test-product13',
            'price' => 99.99,
            'stock' => 10,
            'category_id' => $category->id,
        ]);

        $product = Product::where('name', 'Test Product13')->first();

        // Check primary image exists using the fake disk
        $primaryImage = $product->images()->where('is_primary', true)->first();
        $this->assertNotNull($primaryImage, 'Primary image not found');
        Storage::disk('public')->assertExists($primaryImage->image_path);

        // Check other images using the fake disk
        $otherImages = $product->images()->where('is_primary', false)->get();
        $this->assertCount(2, $otherImages, 'Expected 2 non-primary images');

        foreach ($otherImages as $image) {
            Storage::disk('public')->assertExists($image->image_path);
        }
    }

    // show a product
    public function test_showing_a_product()
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id
        ]);

        // Create a primary image
        $product->images()->create([
            'image_path' => 'test/primary.jpg',
            'is_primary' => true
        ]);

        // Create additional images
        $product->images()->create([
            'image_path' => 'test/image1.jpg',
            'is_primary' => false
        ]);

        $response = $this->getJson(route('products.show', $product->id));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'product' => [
                    'id',
                    'name',
                    'slug',
                    'price',
                    'stock',
                    'category_id',
                    'status',
                    'images' => [
                        '*' => [
                            'id',
                            'image_path',
                            'is_primary',
                            'product_id',
                            'created_at',
                            'updated_at'
                        ]
                    ],
                    'created_at',
                    'updated_at'
                ]
            ]);

        $this->assertEquals($product->id, $response->json('product.id'));
    }

    // update test
    public function test_updating_a_product()
    {
        Storage::fake('public');
        $category = Category::factory()->create();
        $newCategory = Category::factory()->create();

        $product = Product::factory()->create([
            'name' => 'Original Product',
            'category_id' => $category->id,
            'stock' => 5
        ]);

        // Create a primary image
        $product->images()->create([
            'image_path' => 'test/primary.jpg',
            'is_primary' => true
        ]);

        // Create additional images
        $product->images()->create([
            'image_path' => 'test/image1.jpg',
            'is_primary' => false
        ]);

        $updateData = [
            'name' => 'Updated Product',
            'price' => 199.99,
            'stock' => 20,
            'category_id' => $newCategory->id,
            'primary_image' => \Illuminate\Http\UploadedFile::fake()->image('new_primary.jpg'),
            'images' => [
                \Illuminate\Http\UploadedFile::fake()->image('new_image1.jpg'),
                \Illuminate\Http\UploadedFile::fake()->image('new_image2.jpg'),
                \Illuminate\Http\UploadedFile::fake()->image('new_image3.jpg'),
            ],
        ];

        $response = $this->putJson(route('products.update', $product->id), $updateData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'product' => [
                    'id',
                    'name',
                    'slug',
                    'price',
                    'stock',
                    'category_id',
                    'status',
                    'images' => [
                        '*' => [
                            'id',
                            'image_path',
                            'is_primary',
                            'created_at',
                            'updated_at'
                        ]
                    ],
                    'created_at',
                    'updated_at'
                ]
            ]);

        // Check product was updated in database
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Updated Product',
            'slug' => 'updated-product',
            'price' => 199.99,
            'stock' => 20,
            'category_id' => $newCategory->id,
            'status' => 'available'
        ]);

        // Refresh product from database
        $product->refresh();

        // Verify we have one primary image
        $primaryImage = $product->images()->where('is_primary', true)->first();
        $this->assertNotNull($primaryImage);
        Storage::disk('public')->assertExists($primaryImage->image_path);

        // Verify we have 3 non-primary images
        $otherImages = $product->images()->where('is_primary', false)->get();
        $this->assertCount(3, $otherImages);

        foreach ($otherImages as $image) {
            Storage::disk('public')->assertExists($image->image_path);
        }
    }

    public function test_soft_deleting_a_product()
    {
        Storage::fake('public');
        $category = Category::factory()->create();

        $product = Product::factory()->create(['category_id' => $category->id]);

        // Create primary image
        $primaryImagePath = 'products/primary-test.jpg';
        Storage::disk('public')->put($primaryImagePath, 'test contents');

        $product->images()->create([
            'image_path' => $primaryImagePath,
            'is_primary' => true
        ]);

        // Create additional image
        $imagePath = 'products/test-image.jpg';
        Storage::disk('public')->put($imagePath, 'test contents');

        $product->images()->create([
            'image_path' => $imagePath,
            'is_primary' => false
        ]);

        $response = $this->deleteJson(route('products.destroy', $product->id));

        $response->assertStatus(200)
            ->assertJson(['message' => 'Product soft deleted successfully.']);

        // Ensure product is soft deleted (exists in DB but marked as deleted)
        $this->assertSoftDeleted('products', ['id' => $product->id]);

        // Ensure images still exist in DB
        $this->assertDatabaseHas('product_images', ['product_id' => $product->id]);

        // Ensure images are still in storage
        Storage::disk('public')->assertExists($primaryImagePath);
        Storage::disk('public')->assertExists($imagePath);
    }

    public function test_force_deleting_a_product()
    {
        Storage::fake('public');
        $category = Category::factory()->create();

        $product = Product::factory()->create(['category_id' => $category->id]);

        // Create primary image
        $primaryImagePath = 'products/primary-test.jpg';
        Storage::disk('public')->put($primaryImagePath, 'test contents');

        $product->images()->create([
            'image_path' => $primaryImagePath,
            'is_primary' => true
        ]);

        // Create additional image
        $imagePath = 'products/test-image.jpg';
        Storage::disk('public')->put($imagePath, 'test contents');

        $product->images()->create([
            'image_path' => $imagePath,
            'is_primary' => false
        ]);

        // Soft delete first
        $product->delete();
        $this->assertSoftDeleted('products', ['id' => $product->id]);

        // Force delete
        $response = $this->deleteJson(route('products.forcs-destroy', $product->id));

        $response->assertStatus(200)
            ->assertJson(['message' => 'Product and its images permanently deleted successfully.']);

        // Ensure product is permanently deleted
        $this->assertDatabaseMissing('products', ['id' => $product->id]);

        // Ensure associated images are deleted from DB
        $this->assertDatabaseMissing('product_images', ['product_id' => $product->id]);

        // Ensure images are deleted from storage
        Storage::disk('public')->assertMissing($primaryImagePath);
        Storage::disk('public')->assertMissing($imagePath);
    }
}

<?php

namespace Tests\Unit;

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->seed(RoleAndPermissionSeeder::class);

        // Create user and assign 'super_admin' role for testing
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        // Authenticate the admin for all tests
        Sanctum::actingAs($admin, ['*']);
    }

    public function test_index_returns_users_list()
    {
        // Create some users
        User::factory()->count(3)->create();

        $response = $this->getJson(route('users.index'));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'users' => [
                    '*' => [
                        'id',
                        'name',
                        'email',
                        'email_verified_at',
                        'created_at',
                        'updated_at'
                    ]
                ]
            ]);
    }

    public function test_store_creates_new_user()
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'role' => 'product_manager',
        ];

        $response = $this->postJson(route('users.store'), $userData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'user' => [
                    'id',
                    'name',
                    'email',
                    'created_at',
                    'updated_at'
                ]
            ]);

        // Check if user exists in database
        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ]);

        // Check if role was assigned
        $user = User::where('email', 'john@example.com')->first();
        $this->assertTrue($user->hasRole('product_manager'));
    }


    public function test_show_returns_user_details()
    {
        $user = User::factory()->create();
        $user->assignRole('user_manager');

        $response = $this->getJson(route('users.show', $user->id));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'user' => [
                    'id',
                    'name',
                    'email',
                    'created_at',
                    'updated_at'
                ]
            ]);
    }

    public function test_show_returns_404_for_nonexistent_user()
    {
        $response = $this->getJson(route('users.show', 999));
        $response->assertStatus(404);
    }

    public function test_update_modifies_user_details()
    {
        $user = User::factory()->create([
            'name' => 'Original Name',
            'email' => 'original@example.com'
        ]);
        $user->assignRole('product_manager');

        $updateData = [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'role' => 'user_manager',
            'password' => 'newpassword',
            'password_confirmation' => 'newpassword'
        ];

        $response = $this->putJson(route('users.update', $user->id), $updateData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'user' => [
                    'id',
                    'name',
                    'email',
                    'created_at',
                    'updated_at'
                ]
            ])
            ->assertJson([
                'user' => [
                    'name' => 'Updated Name',
                    'email' => 'updated@example.com'
                ]
            ]);

        // Check if user was updated in database
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'email' => 'updated@example.com'
        ]);

        // Check if role was updated
        $this->assertFalse($user->fresh()->hasRole('product_manager'));
        $this->assertTrue($user->fresh()->hasRole('user_manager'));
    }

    public function test_update_without_password_keeps_old_password()
    {
        $user = User::factory()->create([
            'password' => bcrypt('originalpassword')
        ]);
        $originalPasswordHash = $user->password;

        $updateData = [
            'name' => 'Updated Name',
            'email' => $user->email,
            'role' => 'user_manager',
            'password' => '',
            'password_confirmation' => ''
        ];

        $response = $this->putJson(route('users.update', $user->id), $updateData);
        $response->assertStatus(200);

        // Refresh user from database
        $user->refresh();

        // Check that password hasn't changed
        $this->assertEquals($originalPasswordHash, $user->password);
    }

    public function test_update_role_changes_user_role()
    {
        $user = User::factory()->create();
        $user->assignRole('product_manager');

        $response = $this->putJson(route('users.update-role', $user->id), [
            'role' => 'user_manager'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'User role updated successfully.'
            ]);

        // Check if role was updated
        $this->assertFalse($user->fresh()->hasRole('product_manager'));
        $this->assertTrue($user->fresh()->hasRole('user_manager'));
    }

    public function test_destroy_deletes_user()
    {
        $user = User::factory()->create();

        $response = $this->deleteJson(route('users.destroy', $user->id));

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'User deleted successfully.'
            ]);

        // Check if user was deleted from database
        $this->assertSoftDeleted('users', ['id' => $user->id]);

        // Alternative approach using the model
        $this->assertTrue(User::withTrashed()->find($user->id)->trashed());

        // Verify the user is not in the regular query results
        $this->assertNull(User::find($user->id));
    }

    public function test_destroy_returns_404_for_nonexistent_user()
    {
        $response = $this->deleteJson(route('users.destroy', 999));
        $response->assertStatus(404);
    }

    public function test_restore_recovers_soft_deleted_user()
    {
        // Create and soft delete a user
        $user = User::factory()->create();
        $user->delete();

        // Verify user is soft deleted
        $this->assertSoftDeleted('users', ['id' => $user->id]);

        // Test restore endpoint
        $response = $this->putJson(route('users.restore', $user->id));

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'User restored successfully.'
            ]);

        // Verify user is now accessible without withTrashed()
        $this->assertDatabaseHas('users', ['id' => $user->id]);
        $this->assertNotNull(User::find($user->id));
        $this->assertFalse(User::find($user->id)->trashed());
    }
}

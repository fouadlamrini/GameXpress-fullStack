<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view_users')->only(['index', 'show']);
        $this->middleware('permission:create_users')->only('store');
        $this->middleware('permission:edit_users')->only('update');
        $this->middleware('permission:delete_users')->only('destroy');
    }

    public function index()
    {
        $users = User::with('roles')->paginate(10);
        return response()->json($users);
    }

    public function store(StoreUserRequest $request)
    {
        $validated = $request->validated();
        
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password'])
        ]);

        if (!empty($validated['roles'])) {
            $user->assignRole($validated['roles']);
        }

        return response()->json($user->load('roles'), 201);
    }

    public function show(User $user)
    {
        return response()->json($user->load('roles'));
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $validated = $request->validated();

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);

        if (isset($validated['roles'])) {
            $user->syncRoles($validated['roles']);
        }

        return response()->json($user->load('roles'));
    }

    public function destroy(User $user)
    {
        $user->delete();
        return response()->json(null, 204);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $fields = $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|confirmed'
        ]);

        $user = User::create([
            'name' => $fields['name'],
            'email' => $fields['email'],
            'password' => Hash::make($fields['password'])
        ]);

        if (User::count() === 1) {
            $user->assignRole('super_admin');
        } else {
            $user->assignRole('product_manager');
        }

        $token = $user->createToken($request->name);
        return response()->json([
            'user' => $user->load('roles'),
            'token' => $token->plainTextToken
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users',
            'password' => 'required'
        ]);

        $user = User::where('email', $request->email)->first();
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        if (!$user->hasAnyRole(['super_admin', 'product_manager', 'user_manager'])) {
            return response()->json([
                'message' => 'Unauthorized access'
            ], 403);
        }

        $token = $user->createToken($request->email);
        return response()->json([
            'user' => $user->load('roles'),
            'token' => $token->plainTextToken
        ], 200);
    }

    public function logout(Request $request) {
        $request->user()->currentAccessToken()->delete();
        return response()->json([
            'message' => 'Logged out'
        ], 200);
    }
}

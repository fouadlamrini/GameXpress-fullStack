<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionController extends Controller
{
    public function index()
    {
        $roles = Role::with('permissions')->get();

        return response()->json([
            'success' => true,
            'data' => $roles,
        ], 200);
    }

    public function show($roleId)
    {
        $role = Role::with('permissions')->find($roleId);

        if (!$role) {
            return response()->json([
                'success' => false,
                'message' => 'Role not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'role' => $role,
                'permissions' => $role->permissions,
            ],
        ], 200);
    }

    public function addPermission(Request $request, $roleId)
    {
        $request->validate([
            'permission_name' => 'required|string|exists:permissions,name',
        ]);

        $role = Role::find($roleId);

        if (!$role) {
            return response()->json([
                'success' => false,
                'message' => 'Role not found',
            ], 404);
        }

        $permission = Permission::where('name', $request->permission_name)->first();

        if (!$permission) {
            return response()->json([
                'success' => false,
                'message' => 'Permission not found',
            ], 404);
        }

        $role->givePermissionTo($permission);

        return response()->json([
            'success' => true,
            'message' => 'Permission added to role successfully',
            'data' => [
                'role' => $role,
                'permissions' => $role->permissions,
            ],
        ], 200);
    }

    public function removePermission(Request $request, $roleId)
    {
        $request->validate([
            'permission_name' => 'required|string|exists:permissions,name',
        ]);

        $role = Role::find($roleId);

        if (!$role) {
            return response()->json([
                'success' => false,
                'message' => 'Role not found',
            ], 404);
        }

        $permission = Permission::where('name', $request->permission_name)->first();

        if (!$permission) {
            return response()->json([
                'success' => false,
                'message' => 'Permission not found',
            ], 404);
        }

        $role->revokePermissionTo($permission);

        return response()->json([
            'success' => true,
            'message' => 'Permission removed from role successfully',
            'data' => [
                'role' => $role,
                'permissions' => $role->permissions,
            ],
        ], 200);
    }

    public function requestRolePermission(Request $request)
    {
        $request->validate([
            'role_id' => 'required|exists:roles,id',
            'permission_name' => 'required|string|exists:permissions,name',
        ]);

        $role = Role::find($request->role_id);

        $permission = Permission::where('name', $request->permission_name)->first();


        return response()->json([
            'success' => true,
            'message' => 'Permission request sent successfully',
            'data' => [
                'role' => $role,
                'permission' => $permission,
            ],
        ], 200);
    }
}
<?php

declare(strict_types=1);

namespace App\Modules\Administration\Presentation\Http\Controllers;

use App\Modules\Administration\Domain\Models\Admin;
use App\Modules\Administration\Presentation\Http\Requests\AssignRolesRequest;
use App\Modules\Administration\Presentation\Http\Requests\CreateAdminRequest;
use App\Modules\Administration\Presentation\Http\Resources\AdminResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Spatie\Permission\Models\Role;

/**
 * Manage administrators, their roles and the role/permission catalogue.
 */
class AccessControlController extends Controller
{
    public function roles(): JsonResponse
    {
        $roles = Role::query()->where('guard_name', 'admin')->with('permissions')->get()
            ->map(fn (Role $role) => [
                'name' => $role->name,
                'permissions' => $role->permissions->pluck('name')->values(),
            ]);

        return response()->json(['data' => $roles]);
    }

    public function admins(): JsonResponse
    {
        return response()->json([
            'data' => AdminResource::collection(Admin::query()->latest()->get()),
        ]);
    }

    public function storeAdmin(CreateAdminRequest $request): JsonResponse
    {
        $admin = Admin::create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'password' => $request->validated('password'),
            'is_active' => true,
        ]);

        $admin->syncRoles($request->validated('roles', []));

        return (new AdminResource($admin))->response()->setStatusCode(JsonResponse::HTTP_CREATED);
    }

    public function assignRoles(AssignRolesRequest $request, string $admin): AdminResource
    {
        $model = Admin::query()->findOrFail($admin);
        $model->syncRoles($request->validated('roles'));

        return new AdminResource($model->refresh());
    }
}

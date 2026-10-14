<?php

namespace DA\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use DA\Admin\Actions\Tenant\CreateTenantSuperAdmin;
use DA\Admin\Http\Requests\Tenant\StoreTenantSuperAdminRequest;
use DA\Admin\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class TenantSuperAdminController extends Controller
{
    public function store(
        StoreTenantSuperAdminRequest $request,
        Tenant $tenant,
        CreateTenantSuperAdmin $superAdmins,
    ): RedirectResponse|JsonResponse {
        $superAdmins->handle($tenant, $request->toDTO());

        if ($request->expectsJson()) {
            return toJsonWithSaveFlashMessage([
                'status' => 'success',
                'message' => 'Super admin account created successfully',
            ], Response::HTTP_CREATED);
        }

        return toUrlWithSaveMessage(
            route('admin.tenants.show', $tenant),
            'Super admin account created successfully',
        );
    }
}

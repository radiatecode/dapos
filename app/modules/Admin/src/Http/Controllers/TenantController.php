<?php

namespace DA\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use DA\Admin\Actions\Tenant\CreateTenant;
use DA\Admin\Actions\Tenant\UpdateTenant;
use DA\Admin\DataTables\TenantsDataTable;
use DA\Admin\Http\Requests\Tenant\StoreTenantRequest;
use DA\Admin\Http\Requests\Tenant\UpdateTenantRequest;
use DA\Admin\Models\Tenant;
use DA\Admin\Services\Tenancy\TenantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class TenantController extends Controller
{
    public function index(TenantsDataTable $dataTable)
    {
        return $dataTable->render('admin::app.tenants.index');
    }

    public function create(): View
    {
        return view('admin::app.tenants.create', [
            'timezones' => timezone_identifiers_list(),
            'currencies' => ['BDT', 'USD', 'EUR', 'GBP', 'INR', 'AED', 'SGD', 'SAR', 'MYR', 'AUD'],
        ]);
    }

    public function store(StoreTenantRequest $request, CreateTenant $tenants): RedirectResponse|JsonResponse
    {
        $tenants->handle($request->toDTO());

        if ($request->expectsJson()) {
            return toJsonWithSaveFlashMessage([
                'status' => 'success',
                'message' => 'Tenant created successfully',
            ], Response::HTTP_CREATED);
        }

        return toUrlWithSaveMessage(route('admin.tenants.index'), 'Tenant created successfully');
    }

    public function edit(Tenant $tenant)
    {
        return view('admin::app.tenants.edit', compact('tenant'));
    }

    public function update(UpdateTenantRequest $request, Tenant $tenant, UpdateTenant $updateTenant)
    {
        $logo = $request->file('logo');

        $tenant = $updateTenant->handle(
            $tenant,
            $request->toDTO(),
            $logo instanceof UploadedFile ? $logo : null,
        );

    }

    public function destroy(Tenant $tenant, TenantService $tenants): Response
    {
        $tenants->delete($tenant);

        return response()->noContent();
    }
}

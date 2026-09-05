<?php

namespace DA\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use DA\Admin\Actions\Tenant\ChangeTenantStatus;
use DA\Admin\Actions\Tenant\CreateTenant;
use DA\Admin\Actions\Tenant\UpdateTenant;
use DA\Admin\DataTables\TenantsDataTable;
use DA\Admin\Enums\TenantStatus;
use DA\Admin\Http\Requests\Tenant\StoreTenantRequest;
use DA\Admin\Http\Requests\Tenant\UpdateTenantRequest;
use DA\Admin\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        return view('admin::app.tenants.create', $this->formOptions());
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

    public function show(Tenant $tenant): View
    {
        return view('admin::app.tenants.show', compact('tenant'));
    }

    public function edit(Tenant $tenant): View
    {
        return view('admin::app.tenants.edit', [
            'tenant' => $tenant,
            ...$this->formOptions(),
        ]);
    }

    public function update(UpdateTenantRequest $request, Tenant $tenant, UpdateTenant $updateTenant): RedirectResponse|JsonResponse
    {
        $logo = $request->file('logo');

        $tenant = $updateTenant->handle(
            $tenant,
            $request->toDTO(),
            $logo instanceof UploadedFile ? $logo : null,
        );

        if ($request->expectsJson()) {
            return toJsonWithUpdateFlashMessage([
                'status' => 'success',
                'message' => 'Tenant updated successfully',
            ]);
        }

        return toUrlWithUpdatedMessage(route('admin.tenants.show', $tenant), 'Tenant updated successfully');
    }

    public function suspend(Tenant $tenant, ChangeTenantStatus $changeStatus): RedirectResponse|JsonResponse
    {
        $tenant = $changeStatus->handle($tenant, TenantStatus::Suspended);

        return $this->statusChangedResponse(
            request(),
            $tenant,
            'Tenant suspended successfully',
        );
    }

    public function activate(Tenant $tenant, ChangeTenantStatus $changeStatus): RedirectResponse|JsonResponse
    {
        $tenant = $changeStatus->handle($tenant, TenantStatus::Active);

        return $this->statusChangedResponse(
            request(),
            $tenant,
            'Tenant activated successfully',
        );
    }

    public function destroy(Request $request, Tenant $tenant): RedirectResponse|JsonResponse
    {
        $tenant->delete();

        if ($request->ajax() || $request->expectsJson()) {
            return toJsonWithDeleteFlashMessage([
                'status' => 'success',
                'message' => 'Tenant deleted successfully',
            ]);
        }

        return toUrlWithDeletedMessage(route('admin.tenants.index'), 'Tenant deleted successfully');
    }

    /**
     * @return array{timezones: list<string>, currencies: list<string>}
     */
    private function formOptions(): array
    {
        return [
            'timezones' => timezone_identifiers_list(),
            'currencies' => ['BDT', 'USD', 'EUR', 'GBP', 'INR', 'AED', 'SGD', 'SAR', 'MYR', 'AUD'],
        ];
    }

    private function statusChangedResponse(Request $request, Tenant $tenant, string $message): RedirectResponse|JsonResponse
    {
        if ($request->ajax() || $request->expectsJson()) {
            return toJsonWithUpdateFlashMessage([
                'status' => 'success',
                'message' => $message,
            ]);
        }

        return toUrlWithUpdatedMessage(route('admin.tenants.show', $tenant), $message);
    }
}

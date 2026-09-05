<?php

namespace DA\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use DA\Admin\Actions\Addon\CreateAddon;
use DA\Admin\Actions\Addon\UpdateAddon;
use DA\Admin\DataTables\AddonsDataTable;
use DA\Admin\Enums\BillingInterval;
use DA\Admin\Http\Requests\Addon\StoreAddonRequest;
use DA\Admin\Http\Requests\Addon\UpdateAddonRequest;
use DA\Admin\Models\Addon;
use DA\Admin\Models\Currency;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class AddonController extends Controller
{
    public function index(AddonsDataTable $dataTable)
    {
        return $dataTable->render('admin::app.addons.index', [
            'currencies' => Currency::queries()->activeOrderedByCode(),
            'billingIntervals' => BillingInterval::cases(),
        ]);
    }

    public function store(StoreAddonRequest $request, CreateAddon $createAddon): RedirectResponse|JsonResponse
    {
        $createAddon->handle($request->toDTO());

        if ($request->ajax() || $request->expectsJson()) {
            return toJsonWithSaveFlashMessage([
                'status' => 'success',
                'message' => 'Add-on created successfully',
            ], Response::HTTP_CREATED);
        }

        return toUrlWithSaveMessage(route('admin.addons.index'), 'Add-on created successfully');
    }

    public function update(UpdateAddonRequest $request, Addon $addon, UpdateAddon $updateAddon): RedirectResponse|JsonResponse
    {
        $updateAddon->handle($addon, $request->toDTO());

        if ($request->ajax() || $request->expectsJson()) {
            return toJsonWithUpdateFlashMessage([
                'status' => 'success',
                'message' => 'Add-on updated successfully',
            ]);
        }

        return toUrlWithUpdatedMessage(route('admin.addons.index'), 'Add-on updated successfully');
    }
}

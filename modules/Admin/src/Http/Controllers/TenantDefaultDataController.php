<?php

namespace DA\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use DA\Admin\Actions\Tenant\CreateDefaultData;
use DA\Admin\DTO\TenantDefaultData;
use DA\Admin\Enums\DefaultDataType;
use DA\Admin\Models\Tenant;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TenantDefaultDataController extends Controller
{
    public function create(int $id)
    {
        $tenant = Tenant::queries()->findById($id);

        $default_data = $tenant->default_data ? json_decode($tenant->default_data, true) : [];

        $defaultType = DefaultDataType::cases();

        return view('admin::app.default.create', compact('tenant', 'default_data', 'defaultType'));
    }

    public function store(Request $request, int $id)
    {
        $data = TenantDefaultData::from($request->all());

        (new CreateDefaultData)->handle($id, $data);

        return toJsonWithSaveFlashMessage([
            'status' => 'success',
            'message' => 'Default data created successfully',
        ], Response::HTTP_CREATED);
    }
}

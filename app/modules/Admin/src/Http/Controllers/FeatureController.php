<?php

namespace DA\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use DA\Admin\Actions\Feature\CreateFeature;
use DA\Admin\Actions\Feature\UpdateFeature;
use DA\Admin\DataTables\FeaturesDataTable;
use DA\Admin\Enums\FeatureType;
use DA\Admin\Http\Requests\Feature\StoreFeatureRequest;
use DA\Admin\Http\Requests\Feature\UpdateFeatureRequest;
use DA\Admin\Models\Feature;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class FeatureController extends Controller
{
    public function index(FeaturesDataTable $dataTable)
    {
        return $dataTable->render('admin::app.features.index', [
            'featureTypes' => FeatureType::cases(),
        ]);
    }

    public function store(StoreFeatureRequest $request, CreateFeature $createFeature): RedirectResponse|JsonResponse
    {
        $createFeature->handle($request->toDTO());

        if ($request->ajax() || $request->expectsJson()) {
            return toJsonWithSaveFlashMessage([
                'status' => 'success',
                'message' => 'Feature created successfully',
            ], Response::HTTP_CREATED);
        }

        return toUrlWithSaveMessage(route('admin.features.index'), 'Feature created successfully');
    }

    public function update(UpdateFeatureRequest $request, Feature $feature, UpdateFeature $updateFeature): RedirectResponse|JsonResponse
    {
        $updateFeature->handle($feature, $request->toDTO());

        if ($request->ajax() || $request->expectsJson()) {
            return toJsonWithUpdateFlashMessage([
                'status' => 'success',
                'message' => 'Feature updated successfully',
            ]);
        }

        return toUrlWithUpdatedMessage(route('admin.features.index'), 'Feature updated successfully');
    }
}

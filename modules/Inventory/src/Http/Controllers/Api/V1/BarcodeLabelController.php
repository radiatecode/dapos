<?php

namespace DA\Inventory\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use DA\Inventory\Http\Requests\Api\V1\Product\BarcodeLabelRequest;
use DA\Inventory\Http\Resources\Api\V1\BarcodeLabelResource;
use DA\Inventory\Models\ProductVariant;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BarcodeLabelController extends Controller
{
    public function index(BarcodeLabelRequest $request): AnonymousResourceCollection
    {
        return BarcodeLabelResource::collection(
            ProductVariant::queries()->paginateVariantsForBarcodeLabels($request->toDTO(), $request->perPage()),
        );
    }
}

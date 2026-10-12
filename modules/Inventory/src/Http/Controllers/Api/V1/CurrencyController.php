<?php

namespace DA\Inventory\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use DA\Admin\Models\Currency;
use DA\Inventory\Http\Resources\Api\V1\CurrencyResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CurrencyController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return CurrencyResource::collection(
            Currency::queries()->activeOrderedByCode(),
        );
    }
}

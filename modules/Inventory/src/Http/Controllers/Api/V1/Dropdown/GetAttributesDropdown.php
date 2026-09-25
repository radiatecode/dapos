<?php

namespace DA\Inventory\Http\Controllers\Api\V1\Dropdown;

use App\Http\Controllers\Controller;
use App\Services\Select2;
use DA\Inventory\Models\Attribute;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class GetAttributesDropdown extends Controller
{
    public function __invoke(Request $request)
    {
        $attributes = Attribute::queries()->select2($request->input('search'));

        $data = Select2::make($request->input('page'), $request->input('limit'))
            ->query($attributes)
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'label' => $item->name,
                    'name' => $item->name,
                    'code' => $item->code,
                ];
            })
            ->render();

        return response()->json(
            $data,
            Response::HTTP_OK
        );
    }
}

<?php

namespace DA\Inventory\Http\Controllers\Api\V1\Dropdown;

use App\Http\Controllers\Controller;
use App\Services\Select2;
use DA\Inventory\Models\AttributeValue;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class GetAttributeValuesDropdown extends Controller
{
    public function __invoke(Request $request)
    {
        $attributeId = $request->integer('attribute_id');

        if ($attributeId <= 0) {
            return response()->json([
                'results' => [],
                'pagination' => [
                    'more' => false,
                ],
            ], Response::HTTP_OK);
        }

        $values = AttributeValue::queries()->select2($attributeId, $request->input('search'));

        $data = Select2::make((int) $request->input('page', 1), (int) $request->input('limit', 20))
            ->query($values)
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'label' => $item->value,
                    'name' => $item->value,
                    'code' => $item->code,
                    'attribute_id' => $item->attribute_id,
                ];
            })
            ->render();

        return response()->json(
            $data,
            Response::HTTP_OK
        );
    }
}

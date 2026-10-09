<?php

namespace DA\Inventory\Http\Controllers\Api\V1\Dropdown;

use App\Http\Controllers\Controller;
use App\Services\Select2;
use DA\Inventory\Enums\TaxType;
use DA\Inventory\Models\Tax;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class GetTaxesDropdown extends Controller
{
    public function __invoke(Request $request)
    {
        $taxes = Tax::queries()->select2($request->input('search'));

        $data = Select2::make((int) $request->input('page', 1), (int) $request->input('limit', 20))
            ->query($taxes)
            ->map(function (Tax $item) {
                $rate = rtrim(rtrim(number_format((float) $item->tax_rate, 2, '.', ''), '0'), '.');
                $label = (int) $item->tax_type === TaxType::PERCENTAGE->value
                    ? "{$item->tax_name} ({$rate}%)"
                    : "{$item->tax_name} ({$rate})";

                return [
                    'id' => $item->id,
                    'label' => $label,
                    'name' => $item->tax_name,
                ];
            })
            ->render();

        return response()->json(
            $data,
            Response::HTTP_OK
        );
    }
}

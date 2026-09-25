<?php

namespace DA\Inventory\Http\Controllers\Api\V1\Dropdown;

use App\Http\Controllers\Controller;
use App\Services\Select2;
use DA\Inventory\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class GetUnitsDropdown extends Controller
{
    public function __invoke(Request $request)
    {
        $units = Unit::queries()->select2($request->input('search'));

        $data = Select2::make((int) $request->input('page', 1), (int) $request->input('limit', 20))
            ->query($units)
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

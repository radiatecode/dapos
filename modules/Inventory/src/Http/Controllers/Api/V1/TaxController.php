<?php

namespace DA\Inventory\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use DA\Inventory\Actions\Tax\CreateTax;
use DA\Inventory\Actions\Tax\UpdateTax;
use DA\Inventory\Http\Requests\Api\V1\Tax\TaxRequest;
use DA\Inventory\Http\Resources\Api\V1\TaxResource;
use DA\Inventory\Models\Tax;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TaxController extends Controller
{
    public function index()
    {
        return TaxResource::collection(
            Tax::queries()->getTaxes()
        );
    }

    public function store(TaxRequest $request)
    {
        $tax = CreateTax::execute($request->toData());

        return TaxResource::make($tax);
    }

    public function update(TaxRequest $request, $id)
    {
        $tax = UpdateTax::execute($request->toData());

        return TaxResource::make($tax);
    }

    public function show($id)
    {
        return TaxResource::make(
            Tax::queries()->findById($id)
        );
    }

    public function destroy(Request $request): JsonResponse
    {
        if ($request->input('action') !== 'delete') {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid action',
            ], Response::HTTP_BAD_REQUEST);
        }

        $taxes = Tax::queries()->getDataByIds($request->input('ids', []));

        foreach ($taxes as $tax) {
            $tax->delete();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Tax deleted successfully',
        ], Response::HTTP_OK);
    }
}

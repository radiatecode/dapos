<?php

namespace DA\Inventory\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use DA\Inventory\Actions\Product\ImportProductBatch;
use DA\Inventory\Actions\Product\PrepareProductBatchSample;
use DA\Inventory\Actions\Product\VerifyProductBatch;
use DA\Inventory\Http\Requests\Api\V1\Product\ProductBatchRequest;
use DA\Inventory\Http\Resources\Api\V1\ProductResource;
use DA\Inventory\Supports\Product\ProductBatchSampleExport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProductBatchController extends Controller
{
    public function sample(PrepareProductBatchSample $prepare): BinaryFileResponse
    {
        return Excel::download(
            new ProductBatchSampleExport($prepare->handle()),
            'product-batch-sample.xlsx',
        );
    }

    public function verify(ProductBatchRequest $request, VerifyProductBatch $verify): JsonResponse
    {
        $result = $verify->handle($this->spreadsheet($request), $request->integer('store_id'));

        return response()->json($result->preview(), $result->valid ? 200 : 422);
    }

    public function store(ProductBatchRequest $request, ImportProductBatch $import): JsonResponse
    {
        $result = $import->handle($this->spreadsheet($request), $request->integer('store_id'));

        if (! $result->verification->valid) {
            return response()->json($result->verification->preview(), 422);
        }

        return ProductResource::collection($result->products)
            ->additional([
                'message' => 'Products uploaded.',
                'created' => count($result->products),
            ])
            ->response()
            ->setStatusCode(201);
    }

    private function spreadsheet(ProductBatchRequest $request): UploadedFile
    {
        $file = $request->file('file');

        if (! $file instanceof UploadedFile) {
            throw ValidationException::withMessages([
                'file' => 'The spreadsheet file is required.',
            ]);
        }

        return $file;
    }
}

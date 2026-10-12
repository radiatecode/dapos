<?php

namespace DA\Inventory\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Barryvdh\Snappy\Facades\SnappyPdf;
use DA\Inventory\Actions\Report\PrepareMinStockReportExport;
use DA\Inventory\Http\Requests\Api\V1\Report\MinStockReportRequest;
use DA\Inventory\Http\Resources\Api\V1\MinStockReportResource;
use DA\Inventory\Models\ProductVariant;
use DA\Inventory\Supports\Reports\MinStockReportExcelExport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MinStockReportController extends Controller
{
    public function index(MinStockReportRequest $request): AnonymousResourceCollection
    {
        return MinStockReportResource::collection(
            ProductVariant::queries()->paginateMinStockReport($request->toDTO(), $request->perPage()),
        );
    }

    public function summary(MinStockReportRequest $request): JsonResponse
    {
        return response()->json([
            'data' => ProductVariant::queries()->minStockReportTotals($request->toDTO()),
        ]);
    }

    public function exportPdf(MinStockReportRequest $request, PrepareMinStockReportExport $prepare): Response
    {
        $report = $prepare->handle($request->toDTO());

        return SnappyPdf::loadView('inventory::reports.min-stock', ['report' => $report])
            ->setPaper('a4', 'landscape')
            ->setOption('margin-top', 10)
            ->setOption('margin-bottom', 10)
            ->setOption('footer-right', 'Page [page] of [toPage]')
            ->setOption('footer-font-size', 8)
            ->download($report->fileName('pdf'));
    }

    public function exportExcel(MinStockReportRequest $request, PrepareMinStockReportExport $prepare): BinaryFileResponse
    {
        $report = $prepare->handle($request->toDTO());

        return Excel::download(new MinStockReportExcelExport($report), $report->fileName('xlsx'));
    }
}

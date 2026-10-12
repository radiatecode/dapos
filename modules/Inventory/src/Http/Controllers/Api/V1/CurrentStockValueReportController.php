<?php

namespace DA\Inventory\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Barryvdh\Snappy\Facades\SnappyPdf;
use DA\Inventory\Actions\Report\PrepareCurrentStockValueReportExport;
use DA\Inventory\Http\Requests\Api\V1\Report\CurrentStockValueReportRequest;
use DA\Inventory\Http\Resources\Api\V1\CurrentStockValueReportResource;
use DA\Inventory\Models\InventoryBalance;
use DA\Inventory\Supports\Reports\CurrentStockValueReportExcelExport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CurrentStockValueReportController extends Controller
{
    public function index(CurrentStockValueReportRequest $request): AnonymousResourceCollection
    {
        return CurrentStockValueReportResource::collection(
            InventoryBalance::queries()->paginateCurrentStockValueReport($request->toDTO(), $request->perPage()),
        );
    }

    public function summary(CurrentStockValueReportRequest $request): JsonResponse
    {
        return response()->json([
            'data' => InventoryBalance::queries()->currentStockValueReportTotals($request->toDTO()),
        ]);
    }

    public function exportPdf(CurrentStockValueReportRequest $request, PrepareCurrentStockValueReportExport $prepare): Response
    {
        $report = $prepare->handle($request->toDTO());

        return SnappyPdf::loadView('inventory::reports.current-stock-value', ['report' => $report])
            ->setPaper('a4', 'landscape')
            ->setOption('margin-top', 10)
            ->setOption('margin-bottom', 10)
            ->setOption('footer-right', 'Page [page] of [toPage]')
            ->setOption('footer-font-size', 8)
            ->download($report->fileName('pdf'));
    }

    public function exportExcel(CurrentStockValueReportRequest $request, PrepareCurrentStockValueReportExport $prepare): BinaryFileResponse
    {
        $report = $prepare->handle($request->toDTO());

        return Excel::download(new CurrentStockValueReportExcelExport($report), $report->fileName('xlsx'));
    }
}

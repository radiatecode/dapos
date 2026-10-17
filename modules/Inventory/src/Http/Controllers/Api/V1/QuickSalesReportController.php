<?php

namespace DA\Inventory\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Barryvdh\Snappy\Facades\SnappyPdf;
use DA\Inventory\Actions\Report\PrepareQuickSalesReportExport;
use DA\Inventory\Http\Requests\Api\V1\Report\QuickSalesReportRequest;
use DA\Inventory\Http\Resources\Api\V1\QuickSalesReportResource;
use DA\Inventory\Models\QuickSale;
use DA\Inventory\Supports\Reports\QuickSalesReportExcelExport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class QuickSalesReportController extends Controller
{
    public function index(QuickSalesReportRequest $request): AnonymousResourceCollection
    {
        return QuickSalesReportResource::collection(
            QuickSale::queries()->paginateDailyReport($request->toDTO(), $request->perPage()),
        );
    }

    public function summary(QuickSalesReportRequest $request): JsonResponse
    {
        return response()->json([
            'data' => QuickSale::queries()->dailyReportTotals($request->toDTO()),
        ]);
    }

    public function exportPdf(QuickSalesReportRequest $request, PrepareQuickSalesReportExport $prepare): Response
    {
        $report = $prepare->handle($request->toDTO());

        return SnappyPdf::loadView('inventory::reports.quick-sales', ['report' => $report])
            ->setPaper('a4', 'landscape')
            ->setOption('margin-top', 10)
            ->setOption('margin-bottom', 10)
            ->setOption('footer-right', 'Page [page] of [toPage]')
            ->setOption('footer-font-size', 8)
            ->download($report->fileName('pdf'));
    }

    public function exportExcel(QuickSalesReportRequest $request, PrepareQuickSalesReportExport $prepare): BinaryFileResponse
    {
        $report = $prepare->handle($request->toDTO());

        return Excel::download(new QuickSalesReportExcelExport($report), $report->fileName('xlsx'));
    }
}

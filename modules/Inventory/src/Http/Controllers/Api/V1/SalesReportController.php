<?php

namespace DA\Inventory\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Barryvdh\Snappy\Facades\SnappyPdf;
use DA\Inventory\Actions\Report\PrepareSalesReportExport;
use DA\Inventory\Http\Requests\Api\V1\Report\SalesReportRequest;
use DA\Inventory\Http\Resources\Api\V1\SalesReportResource;
use DA\Inventory\Models\SaleItem;
use DA\Inventory\Supports\Reports\SalesReportExcelExport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SalesReportController extends Controller
{
    public function index(SalesReportRequest $request): AnonymousResourceCollection
    {
        return SalesReportResource::collection(
            SaleItem::queries()->paginateSalesReport($request->toDTO(), $request->perPage()),
        );
    }

    public function summary(SalesReportRequest $request): JsonResponse
    {
        return response()->json([
            'data' => SaleItem::queries()->salesReportTotals($request->toDTO()),
        ]);
    }

    public function exportPdf(SalesReportRequest $request, PrepareSalesReportExport $prepare): Response
    {
        $report = $prepare->handle($request->toDTO());

        return SnappyPdf::loadView('inventory::reports.sales', ['report' => $report])
            ->setPaper('a4', 'landscape')
            ->setOption('margin-top', 10)
            ->setOption('margin-bottom', 10)
            ->setOption('footer-right', 'Page [page] of [toPage]')
            ->setOption('footer-font-size', 8)
            ->download($report->fileName('pdf'));
    }

    public function exportExcel(SalesReportRequest $request, PrepareSalesReportExport $prepare): BinaryFileResponse
    {
        $report = $prepare->handle($request->toDTO());

        return Excel::download(new SalesReportExcelExport($report), $report->fileName('xlsx'));
    }
}

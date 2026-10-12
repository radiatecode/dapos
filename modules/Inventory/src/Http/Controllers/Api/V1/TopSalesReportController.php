<?php

namespace DA\Inventory\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Barryvdh\Snappy\Facades\SnappyPdf;
use DA\Inventory\Actions\Report\PrepareTopSalesReportExport;
use DA\Inventory\Http\Requests\Api\V1\Report\TopSalesReportRequest;
use DA\Inventory\Http\Resources\Api\V1\TopSalesReportResource;
use DA\Inventory\Models\SaleItem;
use DA\Inventory\Supports\Reports\TopSalesReportExcelExport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TopSalesReportController extends Controller
{
    public function index(TopSalesReportRequest $request): AnonymousResourceCollection
    {
        return TopSalesReportResource::collection(
            SaleItem::queries()->paginateTopSalesReport($request->toDTO(), $request->perPage()),
        );
    }

    public function summary(TopSalesReportRequest $request): JsonResponse
    {
        return response()->json([
            'data' => SaleItem::queries()->topSalesTotals($request->toDTO()),
        ]);
    }

    public function exportPdf(TopSalesReportRequest $request, PrepareTopSalesReportExport $prepare): Response
    {
        $report = $prepare->handle($request->toDTO());

        return SnappyPdf::loadView('inventory::reports.top-sales', ['report' => $report])
            ->setPaper('a4', 'landscape')
            ->setOption('margin-top', 10)
            ->setOption('margin-bottom', 10)
            ->setOption('footer-right', 'Page [page] of [toPage]')
            ->setOption('footer-font-size', 8)
            ->download($report->fileName('pdf'));
    }

    public function exportExcel(TopSalesReportRequest $request, PrepareTopSalesReportExport $prepare): BinaryFileResponse
    {
        $report = $prepare->handle($request->toDTO());

        return Excel::download(new TopSalesReportExcelExport($report), $report->fileName('xlsx'));
    }
}

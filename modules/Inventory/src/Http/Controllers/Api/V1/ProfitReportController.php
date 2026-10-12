<?php

namespace DA\Inventory\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Barryvdh\Snappy\Facades\SnappyPdf;
use DA\Inventory\Actions\Report\PrepareProfitReportExport;
use DA\Inventory\Http\Requests\Api\V1\Report\ProfitReportRequest;
use DA\Inventory\Http\Resources\Api\V1\ProfitReportResource;
use DA\Inventory\Models\SaleItem;
use DA\Inventory\Supports\Reports\ProfitReportExcelExport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProfitReportController extends Controller
{
    public function index(ProfitReportRequest $request): AnonymousResourceCollection
    {
        return ProfitReportResource::collection(
            SaleItem::queries()->paginateProfitReport($request->toDTO(), $request->perPage()),
        );
    }

    public function summary(ProfitReportRequest $request): JsonResponse
    {
        return response()->json([
            'data' => SaleItem::queries()->profitReportTotals($request->toDTO()),
        ]);
    }

    public function exportPdf(ProfitReportRequest $request, PrepareProfitReportExport $prepare): Response
    {
        $report = $prepare->handle($request->toDTO());

        return SnappyPdf::loadView('inventory::reports.profit', ['report' => $report])
            ->setPaper('a4', 'landscape')
            ->setOption('margin-top', 10)
            ->setOption('margin-bottom', 10)
            ->setOption('footer-right', 'Page [page] of [toPage]')
            ->setOption('footer-font-size', 8)
            ->download($report->fileName('pdf'));
    }

    public function exportExcel(ProfitReportRequest $request, PrepareProfitReportExport $prepare): BinaryFileResponse
    {
        $report = $prepare->handle($request->toDTO());

        return Excel::download(new ProfitReportExcelExport($report), $report->fileName('xlsx'));
    }
}

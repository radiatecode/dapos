<?php

namespace DA\Inventory\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Barryvdh\Snappy\Facades\SnappyPdf;
use DA\Inventory\Actions\Report\PreparePurchaseReportExport;
use DA\Inventory\Http\Requests\Api\V1\Report\PurchaseReportRequest;
use DA\Inventory\Http\Resources\Api\V1\PurchaseResource;
use DA\Inventory\Models\Purchase;
use DA\Inventory\Supports\Reports\PurchaseReportExcelExport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PurchaseReportController extends Controller
{
    public function index(PurchaseReportRequest $request): AnonymousResourceCollection
    {
        return PurchaseResource::collection(
            Purchase::queries()->paginateReport($request->toDTO(), $request->perPage()),
        );
    }

    public function summary(PurchaseReportRequest $request): JsonResponse
    {
        return response()->json([
            'data' => Purchase::queries()->reportTotals($request->toDTO()),
        ]);
    }

    public function exportPdf(PurchaseReportRequest $request, PreparePurchaseReportExport $prepare): Response
    {
        $report = $prepare->handle($request->toDTO());

        return SnappyPdf::loadView('inventory::reports.purchases', ['report' => $report])
            ->setPaper('a4', 'landscape')
            ->setOption('margin-top', 10)
            ->setOption('margin-bottom', 10)
            ->setOption('footer-right', 'Page [page] of [toPage]')
            ->setOption('footer-font-size', 8)
            ->download($report->fileName('pdf'));
    }

    public function exportExcel(PurchaseReportRequest $request, PreparePurchaseReportExport $prepare): BinaryFileResponse
    {
        $report = $prepare->handle($request->toDTO());

        return Excel::download(new PurchaseReportExcelExport($report), $report->fileName('xlsx'));
    }
}

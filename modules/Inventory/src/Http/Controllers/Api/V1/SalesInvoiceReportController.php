<?php

namespace DA\Inventory\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Barryvdh\Snappy\Facades\SnappyPdf;
use DA\Inventory\Actions\Report\PrepareSalesInvoiceReportExport;
use DA\Inventory\Http\Requests\Api\V1\Report\SalesInvoiceReportRequest;
use DA\Inventory\Supports\Reports\SalesInvoiceReportExcelExport;
use Illuminate\Http\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SalesInvoiceReportController extends Controller
{
    public function exportPdf(SalesInvoiceReportRequest $request, PrepareSalesInvoiceReportExport $prepare): Response
    {
        $report = $prepare->handle($request->toDTO());

        return SnappyPdf::loadView('inventory::reports.sales-invoices', ['report' => $report])
            ->setPaper('a4', 'landscape')
            ->setOption('margin-top', 10)
            ->setOption('margin-bottom', 10)
            ->setOption('footer-right', 'Page [page] of [toPage]')
            ->setOption('footer-font-size', 8)
            ->download($report->fileName('pdf'));
    }

    public function exportExcel(SalesInvoiceReportRequest $request, PrepareSalesInvoiceReportExport $prepare): BinaryFileResponse
    {
        $report = $prepare->handle($request->toDTO());

        return Excel::download(new SalesInvoiceReportExcelExport($report), $report->fileName('xlsx'));
    }
}

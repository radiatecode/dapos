<?php

namespace DA\Expense\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Barryvdh\Snappy\Facades\SnappyPdf;
use DA\Expense\Actions\Report\PrepareExpenseReportExport;
use DA\Expense\Http\Requests\Api\V1\Report\ExpenseReportRequest;
use DA\Expense\Http\Resources\Api\V1\ExpenseResource;
use DA\Expense\Models\Expense;
use DA\Expense\Supports\Reports\ExpenseReportExcelExport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExpenseReportController extends Controller
{
    public function index(ExpenseReportRequest $request): AnonymousResourceCollection
    {
        return ExpenseResource::collection(
            Expense::queries()->paginateReport($request->toDTO(), $request->perPage()),
        );
    }

    public function summary(ExpenseReportRequest $request): JsonResponse
    {
        return response()->json([
            'data' => Expense::queries()->reportTotals($request->toDTO()),
        ]);
    }

    public function exportPdf(ExpenseReportRequest $request, PrepareExpenseReportExport $prepare): Response
    {
        $report = $prepare->handle($request->toDTO());

        return SnappyPdf::loadView('expense::reports.expenses', ['report' => $report])
            ->setPaper('a4', 'landscape')
            ->setOption('margin-top', 10)
            ->setOption('margin-bottom', 10)
            ->setOption('footer-right', 'Page [page] of [toPage]')
            ->setOption('footer-font-size', 8)
            ->download($report->fileName('pdf'));
    }

    public function exportExcel(ExpenseReportRequest $request, PrepareExpenseReportExport $prepare): BinaryFileResponse
    {
        $report = $prepare->handle($request->toDTO());

        return Excel::download(new ExpenseReportExcelExport($report), $report->fileName('xlsx'));
    }
}

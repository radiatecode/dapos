<?php

namespace DA\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use DA\Admin\DataTables\InvoicesDataTable;
use DA\Admin\Enums\PaymentMethod;
use DA\Admin\Http\Requests\Invoice\RecordPaymentRequest;
use DA\Admin\Http\Requests\Invoice\StoreInvoiceRequest;
use DA\Admin\Models\Invoice;
use DA\Admin\Models\Subscription;
use DA\Admin\Services\InvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class InvoiceController extends Controller
{
    public function index(InvoicesDataTable $dataTable)
    {
        return $dataTable->render('admin::app.invoices.index');
    }

    public function create(): View
    {
        return view('admin::app.invoices.create', [
            'subscriptions' => Subscription::query()
                ->current()
                ->with(['tenant', 'plan'])
                ->orderByDesc('id')
                ->get(),
        ]);
    }

    public function store(StoreInvoiceRequest $request, InvoiceService $invoices): RedirectResponse|JsonResponse
    {
        $invoice = $invoices->generate($request->toDTO());

        if ($request->ajax() || $request->expectsJson()) {
            return toJsonWithSaveFlashMessage([
                'status' => 'success',
                'message' => 'Invoice generated successfully',
            ], Response::HTTP_CREATED);
        }

        return toUrlWithSaveMessage(route('admin.invoices.show', $invoice), 'Invoice generated successfully');
    }

    public function show(Invoice $invoice): View
    {
        $invoice->load([
            'tenant',
            'subscription.plan',
            'currency',
            'items',
            'payments',
            'couponRedemption.coupon',
        ]);

        return view('admin::app.invoices.show', [
            'invoice' => $invoice,
            'paymentMethods' => PaymentMethod::cases(),
        ]);
    }

    public function markPaid(RecordPaymentRequest $request, Invoice $invoice, InvoiceService $invoices): RedirectResponse|JsonResponse
    {
        $invoices->markPaid($invoice, $request->toDTO());

        if ($request->ajax() || $request->expectsJson()) {
            return toJsonWithUpdateFlashMessage([
                'status' => 'success',
                'message' => 'Invoice marked as paid',
            ]);
        }

        return toUrlWithUpdatedMessage(route('admin.invoices.show', $invoice), 'Invoice marked as paid');
    }

    public function markFailed(RecordPaymentRequest $request, Invoice $invoice, InvoiceService $invoices): RedirectResponse|JsonResponse
    {
        $invoices->markFailed($invoice, $request->toDTO());

        if ($request->ajax() || $request->expectsJson()) {
            return toJsonWithUpdateFlashMessage([
                'status' => 'success',
                'message' => 'Invoice marked as failed',
            ]);
        }

        return toUrlWithUpdatedMessage(route('admin.invoices.show', $invoice), 'Invoice marked as failed');
    }
}

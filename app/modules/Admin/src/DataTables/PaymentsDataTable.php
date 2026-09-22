<?php

namespace DA\Admin\DataTables;

use DA\Admin\Enums\PaymentMethod;
use DA\Admin\Enums\PaymentStatus;
use DA\Admin\Models\Queries\SubscriptionPaymentQueries;
use Illuminate\Database\Query\Builder;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Layout;
use Yajra\DataTables\QueryDataTable;
use Yajra\DataTables\Services\DataTable;

class PaymentsDataTable extends DataTable
{
    public function dataTable(Builder $query): QueryDataTable
    {
        return (new QueryDataTable($query))
            ->editColumn('status', function ($row) {
                $status = PaymentStatus::tryFrom((string) $row->status);

                return $status?->label() ?? $row->status;
            })
            ->editColumn('payment_method', function ($row) {
                $method = PaymentMethod::tryFrom((string) $row->payment_method);

                return $method?->label() ?? $row->payment_method;
            })
            ->editColumn('amount', function ($row) {
                return trim(($row->currency_code ?? '').' '.$row->amount);
            })
            ->addColumn('action', function ($row) {
                return "<a href='".route('admin.invoices.show', $row->invoice_id)."' class='btn btn-sm admin-table-btn admin-table-btn-view'><i class='fas fa-eye'></i> Invoice</a>";
            })
            ->setRowId('id')
            ->rawColumns(['action']);
    }

    public function query(SubscriptionPaymentQueries $queries): Builder
    {
        return $queries->datatable();
    }

    public function html(): HtmlBuilder
    {
        $buttons = [];
        $buttons[] = Button::make('reset')->className('btn admin-btn btn-secondary');
        $buttons[] = Button::make('reload')->className('btn admin-btn btn-light');

        return $this->builder()
            ->setTableId('payments-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->orderBy(1)
            ->layout(fn (Layout $layout) => $layout
                ->topStart(['buttons'])
                ->topEnd('search')
                ->bottomStart(['info', 'pageLength'])
                ->bottomEnd('paging'))
            ->buttons($buttons)
            ->initComplete("function () {
                dtFooter('payments-table');
            }");
    }

    /**
     * @return list<Column>
     */
    public function getColumns(): array
    {
        return [
            Column::make('id'),
            Column::make('invoice_number')->title('Invoice')->name('invoices.invoice_number'),
            Column::make('tenant_name')->title('Tenant')->name('tenants.name'),
            Column::make('amount'),
            Column::make('payment_method')->title('Method'),
            Column::make('status'),
            Column::make('transaction_id')->title('Transaction'),
            Column::make('paid_at')->title('Paid at'),
            Column::computed('action')
                ->exportable(false)
                ->printable(false)
                ->width(60),
        ];
    }

    protected function filename(): string
    {
        return 'Payments_'.date('YmdHis');
    }
}

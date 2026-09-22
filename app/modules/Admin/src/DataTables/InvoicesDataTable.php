<?php

namespace DA\Admin\DataTables;

use DA\Admin\Enums\InvoiceStatus;
use DA\Admin\Models\Queries\InvoiceQueries;
use Illuminate\Database\Query\Builder;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Layout;
use Yajra\DataTables\QueryDataTable;
use Yajra\DataTables\Services\DataTable;

class InvoicesDataTable extends DataTable
{
    public function dataTable(Builder $query): QueryDataTable
    {
        return (new QueryDataTable($query))
            ->editColumn('status', function ($row) {
                $status = InvoiceStatus::tryFrom((string) $row->status);

                return $status?->label() ?? $row->status;
            })
            ->editColumn('total_amount', function ($row) {
                return trim(($row->currency_code ?? '').' '.$row->total_amount);
            })
            ->addColumn('action', function ($row) {
                return "<a href='".route('admin.invoices.show', $row->id)."' class='btn btn-sm admin-table-btn admin-table-btn-view'><i class='fas fa-eye'></i> Details</a>";
            })
            ->setRowId('id')
            ->rawColumns(['action']);
    }

    public function query(InvoiceQueries $queries): Builder
    {
        return $queries->datatable();
    }

    public function html(): HtmlBuilder
    {
        $buttons = [];

        $buttons[] = Button::make('create')->action("window.location='".route('admin.invoices.create')."'")
            ->className('btn admin-btn btn-primary')
            ->text('<i class="fa fa-plus"></i> Generate Invoice');

        $buttons[] = Button::make('reset')->className('btn admin-btn btn-secondary');
        $buttons[] = Button::make('reload')->className('btn admin-btn btn-light');

        return $this->builder()
            ->setTableId('invoices-table')
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
                dtFooter('invoices-table');
            }");
    }

    /**
     * @return list<Column>
     */
    public function getColumns(): array
    {
        return [
            Column::make('id'),
            Column::make('invoice_number')->title('Number'),
            Column::make('tenant_name')->title('Tenant')->name('tenants.name'),
            Column::make('status'),
            Column::make('total_amount')->title('Total'),
            Column::make('due_date')->title('Due'),
            Column::make('created_at'),
            Column::computed('action')
                ->exportable(false)
                ->printable(false)
                ->width(60),
        ];
    }

    protected function filename(): string
    {
        return 'Invoices_'.date('YmdHis');
    }
}

<?php

namespace DA\Admin\DataTables;

use DA\Admin\Models\Queries\PlanQueries;
use Illuminate\Database\Query\Builder;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Layout;
use Yajra\DataTables\QueryDataTable;
use Yajra\DataTables\Services\DataTable;

class PlansDataTable extends DataTable
{
    public function dataTable(Builder $query): QueryDataTable
    {
        return (new QueryDataTable($query))
            ->editColumn('price', function ($row) {
                $currency = $row->currency_code ?? '';

                return trim($currency.' '.$row->price);
            })
            ->editColumn('is_active', function ($row) {
                return $row->is_active ? 'Active' : 'Inactive';
            })
            ->addColumn('action', function ($row) {
                $actions = '';

                $actions .= "<a href='".route('admin.plans.show', $row->id)."' class='btn btn-sm admin-table-btn admin-table-btn-view me-1'><i class='fas fa-eye'></i> Details</a>";
                $actions .= "<a href='".route('admin.plans.edit', $row->id)."' class='btn btn-sm admin-table-btn admin-table-btn-edit me-1'><i class='fas fa-edit'></i> Edit</a>";

                return $actions;
            })
            ->setRowId('id')
            ->rawColumns(['action']);
    }

    public function query(PlanQueries $queries): Builder
    {
        return $queries->datatable();
    }

    public function html(): HtmlBuilder
    {
        $buttons = [];

        $buttons[] = Button::make('create')->action("window.location='".route('admin.plans.create')."'")
            ->className('btn admin-btn btn-primary')
            ->text('<i class="fa fa-plus"></i> Create Plan');

        $buttons[] = Button::make('reset')->className('btn admin-btn btn-secondary');
        $buttons[] = Button::make('reload')->className('btn admin-btn btn-light');

        return $this->builder()
            ->setTableId('plans-table')
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
                dtFooter('plans-table');
            }");
    }

    /**
     * @return list<Column>
     */
    public function getColumns(): array
    {
        return [
            Column::make('id'),
            Column::make('name'),
            Column::make('code'),
            Column::make('billing_interval')->title('Interval'),
            Column::make('price'),
            Column::make('trial_days')->title('Trial days'),
            Column::make('is_active')->title('Status'),
            Column::make('created_at'),
            Column::computed('action')
                ->exportable(false)
                ->printable(false)
                ->width(60),
        ];
    }

    protected function filename(): string
    {
        return 'Plans_'.date('YmdHis');
    }
}

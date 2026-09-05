<?php

namespace DA\Admin\DataTables;

use DA\Admin\Enums\SubscriptionStatus;
use DA\Admin\Models\Queries\SubscriptionQueries;
use Illuminate\Database\Query\Builder;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Layout;
use Yajra\DataTables\QueryDataTable;
use Yajra\DataTables\Services\DataTable;

class SubscriptionsDataTable extends DataTable
{
    public function dataTable(Builder $query): QueryDataTable
    {
        return (new QueryDataTable($query))
            ->editColumn('status', function ($row) {
                $status = SubscriptionStatus::tryFrom((string) $row->status);

                return $status?->label() ?? $row->status;
            })
            ->addColumn('action', function ($row) {
                return "<a href='".route('admin.subscriptions.show', $row->id)."' class='btn btn-sm admin-table-btn admin-table-btn-view'><i class='fas fa-eye'></i> Details</a>";
            })
            ->setRowId('id')
            ->rawColumns(['action']);
    }

    public function query(SubscriptionQueries $queries): Builder
    {
        return $queries->datatable();
    }

    public function html(): HtmlBuilder
    {
        $buttons = [];

        $buttons[] = Button::make('create')->action("window.location='".route('admin.subscriptions.create')."'")
            ->className('btn admin-btn btn-primary')
            ->text('<i class="fa fa-plus"></i> Create Subscription');

        $buttons[] = Button::make('reset')->className('btn admin-btn btn-secondary');
        $buttons[] = Button::make('reload')->className('btn admin-btn btn-light');

        return $this->builder()
            ->setTableId('subscriptions-table')
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
                dtFooter('subscriptions-table');
            }");
    }

    /**
     * @return list<Column>
     */
    public function getColumns(): array
    {
        return [
            Column::make('id'),
            Column::make('tenant_name')->title('Tenant')->name('tenants.name'),
            Column::make('plan_name')->title('Plan')->name('plans.name'),
            Column::make('status'),
            Column::make('trial_ends_at')->title('Trial ends'),
            Column::make('current_period_start')->title('Period start'),
            Column::make('current_period_end')->title('Period end'),
            Column::make('created_at'),
            Column::computed('action')
                ->exportable(false)
                ->printable(false)
                ->width(60),
        ];
    }

    protected function filename(): string
    {
        return 'Subscriptions_'.date('YmdHis');
    }
}

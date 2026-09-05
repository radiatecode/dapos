<?php

namespace DA\Admin\DataTables;

use DA\Admin\Enums\SubscriptionEventType;
use DA\Admin\Enums\SubscriptionStatus;
use DA\Admin\Models\Queries\SubscriptionEventQueries;
use Illuminate\Database\Query\Builder;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Layout;
use Yajra\DataTables\QueryDataTable;
use Yajra\DataTables\Services\DataTable;

class SubscriptionEventsDataTable extends DataTable
{
    public function dataTable(Builder $query): QueryDataTable
    {
        return (new QueryDataTable($query))
            ->editColumn('event_type', function ($row) {
                $type = SubscriptionEventType::tryFrom((string) $row->event_type);

                return $type?->label() ?? $row->event_type;
            })
            ->editColumn('old_status', function ($row) {
                return SubscriptionStatus::tryFrom((string) $row->old_status)?->label() ?? $row->old_status ?? '—';
            })
            ->editColumn('new_status', function ($row) {
                return SubscriptionStatus::tryFrom((string) $row->new_status)?->label() ?? $row->new_status ?? '—';
            })
            ->addColumn('action', function ($row) {
                return "<a href='".route('admin.subscriptions.show', $row->subscription_id)."' class='btn btn-sm admin-table-btn admin-table-btn-view'><i class='fas fa-eye'></i> Subscription</a>";
            })
            ->setRowId('id')
            ->rawColumns(['action']);
    }

    public function query(SubscriptionEventQueries $queries): Builder
    {
        return $queries->datatable();
    }

    public function html(): HtmlBuilder
    {
        $buttons = [];
        $buttons[] = Button::make('reset')->className('btn admin-btn btn-secondary');
        $buttons[] = Button::make('reload')->className('btn admin-btn btn-light');

        return $this->builder()
            ->setTableId('subscription-events-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->orderBy(6, 'desc')
            ->layout(fn (Layout $layout) => $layout
                ->topStart(['buttons'])
                ->topEnd('search')
                ->bottomStart(['info', 'pageLength'])
                ->bottomEnd('paging'))
            ->buttons($buttons)
            ->initComplete("function () {
                dtFooter('subscription-events-table');
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
            Column::make('event_type')->title('Event'),
            Column::make('old_status')->title('From'),
            Column::make('new_status')->title('To'),
            Column::make('occurred_at')->title('Occurred'),
            Column::computed('action')
                ->exportable(false)
                ->printable(false)
                ->width(60),
        ];
    }

    protected function filename(): string
    {
        return 'SubscriptionEvents_'.date('YmdHis');
    }
}

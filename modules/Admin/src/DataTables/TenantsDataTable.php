<?php

namespace DA\Admin\DataTables;

use DA\Admin\Models\Queries\TenantQueries;
use Illuminate\Database\Query\Builder;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Layout;
use Yajra\DataTables\QueryDataTable;
use Yajra\DataTables\Services\DataTable;

class TenantsDataTable extends DataTable
{
    public function dataTable(Builder $query): QueryDataTable
    {
        return (new QueryDataTable($query))
            ->addColumn('action', function ($row) {
                $actions = '';

                $actions .= "<a href='".route('admin.tenants.show', $row->id)."' class='btn btn-sm admin-table-btn admin-table-btn-view me-1'><i class='fas fa-eye'></i> Details</a>";
                $actions .= "<a href='".route('admin.tenants.edit', $row->id)."' class='btn btn-sm admin-table-btn admin-table-btn-edit me-1'><i class='fas fa-edit'></i> Edit</a>";
                $actions .= "<button type='button' class='btn btn-sm admin-table-btn admin-table-btn-delete deletable' data-delete-url='".route(
                    'admin.tenants.destroy',
                    $row->id
                )."' title='Delete Tenant'><i class='fa fa-trash'></i> Delete</button>";

                return $actions;
            })
            ->setRowId('id')
            ->rawColumns(['action']);
    }

    public function query(TenantQueries $queries): Builder
    {
        return $queries->datatable();
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        $buttons = [];

        $buttons[] = Button::make('create')->action("window.location='".route('admin.tenants.create')."'")
            ->className('btn admin-btn btn-primary')
            ->text('<i class="fa fa-plus"></i> Create Tenant');

        $buttons[] = Button::make('reset')->className('btn admin-btn btn-secondary');
        $buttons[] = Button::make('reload')->className('btn admin-btn btn-light');

        return $this->builder()
            ->setTableId('tenants-table')
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
                $('.deletable').delete();

                dtFooter('tenants-table');
            }");
    }

    /**
     * Get the dataTable columns definition.
     */
    public function getColumns(): array
    {
        return [
            Column::make('id'),
            Column::make('name'),
            Column::make('slug'),
            Column::make('country'),
            Column::make('timezone'),
            Column::make('currency'),
            Column::make('contact_person_name')->title('Contact Person'),
            Column::make('contact_person_email')->title('Contact Email'),
            Column::make('contact_person_phone')->title('Contact Phone'),
            Column::make('status'),
            Column::make('created_at'),
            Column::make('updated_at'),
            Column::computed('action')
                ->exportable(false)
                ->printable(false)
                ->width(60),
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'Tenants_'.date('YmdHis');
    }
}

<?php

namespace DA\Admin\DataTables;

use DA\Admin\Models\Queries\FeatureQueries;
use Illuminate\Database\Query\Builder;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Layout;
use Yajra\DataTables\QueryDataTable;
use Yajra\DataTables\Services\DataTable;

class FeaturesDataTable extends DataTable
{
    public function dataTable(Builder $query): QueryDataTable
    {
        return (new QueryDataTable($query))
            ->addColumn('action', function ($row) {
                $payload = e(json_encode([
                    'id' => $row->id,
                    'name' => $row->name,
                    'code' => $row->code,
                    'type' => $row->type,
                    'description' => $row->description,
                    'update_url' => route('admin.features.update', $row->id),
                ], JSON_THROW_ON_ERROR));

                return "<button type='button' class='btn btn-sm admin-table-btn admin-table-btn-edit js-edit-feature' data-feature='{$payload}'><i class='fas fa-edit'></i> Edit</button>";
            })
            ->setRowId('id')
            ->rawColumns(['action']);
    }

    public function query(FeatureQueries $queries): Builder
    {
        return $queries->datatable();
    }

    public function html(): HtmlBuilder
    {
        $buttons = [];

        $buttons[] = Button::make('create')
            ->action("document.getElementById('open-feature-modal')?.click()")
            ->className('btn admin-btn btn-primary')
            ->text('<i class="fa fa-plus"></i> Create Feature');

        $buttons[] = Button::make('reset')->className('btn admin-btn btn-secondary');
        $buttons[] = Button::make('reload')->className('btn admin-btn btn-light');

        return $this->builder()
            ->setTableId('features-table')
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
                dtFooter('features-table');
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
            Column::make('type'),
            Column::make('description'),
            Column::make('created_at'),
            Column::computed('action')
                ->exportable(false)
                ->printable(false)
                ->width(60),
        ];
    }

    protected function filename(): string
    {
        return 'Features_'.date('YmdHis');
    }
}

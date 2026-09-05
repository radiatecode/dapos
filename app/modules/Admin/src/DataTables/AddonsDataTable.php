<?php

namespace DA\Admin\DataTables;

use DA\Admin\Models\Queries\AddonQueries;
use Illuminate\Database\Query\Builder;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Layout;
use Yajra\DataTables\QueryDataTable;
use Yajra\DataTables\Services\DataTable;

class AddonsDataTable extends DataTable
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
                $payload = e(json_encode([
                    'id' => $row->id,
                    'name' => $row->name,
                    'code' => $row->code,
                    'description' => $row->description,
                    'billing_interval' => $row->billing_interval,
                    'price' => $row->price,
                    'currency_id' => $row->currency_id,
                    'is_active' => (bool) $row->is_active,
                    'update_url' => route('admin.addons.update', $row->id),
                ], JSON_THROW_ON_ERROR));

                return "<button type='button' class='btn btn-sm admin-table-btn admin-table-btn-edit js-edit-addon' data-addon='{$payload}'><i class='fas fa-edit'></i> Edit</button>";
            })
            ->setRowId('id')
            ->rawColumns(['action']);
    }

    public function query(AddonQueries $queries): Builder
    {
        return $queries->datatable();
    }

    public function html(): HtmlBuilder
    {
        $buttons = [];

        $buttons[] = Button::make('create')
            ->action("document.getElementById('open-addon-modal')?.click()")
            ->className('btn admin-btn btn-primary')
            ->text('<i class="fa fa-plus"></i> Create Add-on');

        $buttons[] = Button::make('reset')->className('btn admin-btn btn-secondary');
        $buttons[] = Button::make('reload')->className('btn admin-btn btn-light');

        return $this->builder()
            ->setTableId('addons-table')
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
                dtFooter('addons-table');
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
        return 'Addons_'.date('YmdHis');
    }
}

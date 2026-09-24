<?php

namespace DA\Admin\DataTables;

use DA\Admin\Enums\CouponDiscountType;
use DA\Admin\Models\Queries\CouponQueries;
use Illuminate\Database\Query\Builder;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Layout;
use Yajra\DataTables\QueryDataTable;
use Yajra\DataTables\Services\DataTable;

class CouponsDataTable extends DataTable
{
    public function dataTable(Builder $query): QueryDataTable
    {
        return (new QueryDataTable($query))
            ->editColumn('discount_type', function ($row) {
                $type = CouponDiscountType::tryFrom((string) $row->discount_type);

                return $type?->label() ?? $row->discount_type;
            })
            ->editColumn('discount_value', function ($row) {
                $type = CouponDiscountType::tryFrom((string) $row->discount_type);

                if ($type === CouponDiscountType::Percentage) {
                    return rtrim(rtrim((string) $row->discount_value, '0'), '.').'%';
                }

                return trim(($row->currency_code ?? '').' '.$row->discount_value);
            })
            ->editColumn('is_active', function ($row) {
                return $row->is_active ? 'Active' : 'Inactive';
            })
            ->addColumn('action', function ($row) {
                $payload = e(json_encode([
                    'id' => $row->id,
                    'code' => $row->code,
                    'name' => $row->name,
                    'description' => $row->description,
                    'discount_type' => $row->discount_type,
                    'discount_value' => $row->discount_value,
                    'currency_id' => $row->currency_id,
                    'max_redemptions' => $row->max_redemptions,
                    'max_redemptions_per_tenant' => $row->max_redemptions_per_tenant,
                    'minimum_amount' => $row->minimum_amount,
                    'starts_at' => $row->starts_at,
                    'ends_at' => $row->ends_at,
                    'is_active' => (bool) $row->is_active,
                    'update_url' => route('admin.coupons.update', $row->id),
                ], JSON_THROW_ON_ERROR));

                return "<button type='button' class='btn btn-sm admin-table-btn admin-table-btn-edit js-edit-coupon' data-coupon='{$payload}'><i class='fas fa-edit'></i> Edit</button>";
            })
            ->setRowId('id')
            ->rawColumns(['action']);
    }

    public function query(CouponQueries $queries): Builder
    {
        return $queries->datatable();
    }

    public function html(): HtmlBuilder
    {
        $buttons = [];

        $buttons[] = Button::make('create')
            ->action("document.getElementById('open-coupon-modal')?.click()")
            ->className('btn admin-btn btn-primary')
            ->text('<i class="fa fa-plus"></i> Create Coupon');

        $buttons[] = Button::make('reset')->className('btn admin-btn btn-secondary');
        $buttons[] = Button::make('reload')->className('btn admin-btn btn-light');

        return $this->builder()
            ->setTableId('coupons-table')
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
                dtFooter('coupons-table');
            }");
    }

    /**
     * @return list<Column>
     */
    public function getColumns(): array
    {
        return [
            Column::make('id'),
            Column::make('code'),
            Column::make('name'),
            Column::make('discount_type')->title('Type'),
            Column::make('discount_value')->title('Value'),
            Column::make('is_active')->title('Status'),
            Column::make('ends_at')->title('Ends'),
            Column::make('created_at'),
            Column::computed('action')
                ->exportable(false)
                ->printable(false)
                ->width(60),
        ];
    }

    protected function filename(): string
    {
        return 'Coupons_'.date('YmdHis');
    }
}

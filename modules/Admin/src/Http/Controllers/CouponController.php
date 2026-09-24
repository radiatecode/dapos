<?php

namespace DA\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use DA\Admin\DataTables\CouponsDataTable;
use DA\Admin\Enums\CouponDiscountType;
use DA\Admin\Http\Requests\Coupon\StoreCouponRequest;
use DA\Admin\Http\Requests\Coupon\UpdateCouponRequest;
use DA\Admin\Models\Coupon;
use DA\Admin\Models\Currency;
use DA\Admin\Services\CouponService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class CouponController extends Controller
{
    public function index(CouponsDataTable $dataTable)
    {
        return $dataTable->render('admin::app.coupons.index', [
            'discountTypes' => CouponDiscountType::cases(),
            'currencies' => Currency::queries()->activeOrderedByCode(),
        ]);
    }

    public function store(StoreCouponRequest $request, CouponService $coupons): RedirectResponse|JsonResponse
    {
        $coupons->create($request->toDTO());

        if ($request->ajax() || $request->expectsJson()) {
            return toJsonWithSaveFlashMessage([
                'status' => 'success',
                'message' => 'Coupon created successfully',
            ], Response::HTTP_CREATED);
        }

        return toUrlWithSaveMessage(route('admin.coupons.index'), 'Coupon created successfully');
    }

    public function update(UpdateCouponRequest $request, Coupon $coupon, CouponService $coupons): RedirectResponse|JsonResponse
    {
        $coupons->update($coupon, $request->toDTO());

        if ($request->ajax() || $request->expectsJson()) {
            return toJsonWithUpdateFlashMessage([
                'status' => 'success',
                'message' => 'Coupon updated successfully',
            ]);
        }

        return toUrlWithUpdatedMessage(route('admin.coupons.index'), 'Coupon updated successfully');
    }
}

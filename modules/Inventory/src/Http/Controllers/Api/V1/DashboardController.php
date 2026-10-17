<?php

namespace DA\Inventory\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use DA\Inventory\Actions\Dashboard\PrepareDashboardQuickSalesToday;
use DA\Inventory\Actions\Dashboard\PrepareDashboardSalesTrend;
use DA\Inventory\Actions\Dashboard\PrepareDashboardSummary;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function summary(PrepareDashboardSummary $prepare): JsonResponse
    {
        return response()->json(['data' => $prepare->handle()]);
    }

    public function salesTrend(PrepareDashboardSalesTrend $prepare): JsonResponse
    {
        return response()->json(['data' => $prepare->handle()]);
    }

    public function quickSalesToday(PrepareDashboardQuickSalesToday $prepare): JsonResponse
    {
        return response()->json(['data' => $prepare->handle()]);
    }
}

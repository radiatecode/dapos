<?php

namespace DA\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use DA\Admin\Actions\Plan\ChangePlanStatus;
use DA\Admin\Actions\Plan\CreatePlan;
use DA\Admin\Actions\Plan\UpdatePlan;
use DA\Admin\DataTables\PlansDataTable;
use DA\Admin\Enums\BillingInterval;
use DA\Admin\Http\Requests\Plan\StorePlanRequest;
use DA\Admin\Http\Requests\Plan\UpdatePlanRequest;
use DA\Admin\Models\Currency;
use DA\Admin\Models\Feature;
use DA\Admin\Models\Plan;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class PlanController extends Controller
{
    public function index(PlansDataTable $dataTable)
    {
        return $dataTable->render('admin::app.plans.index');
    }

    public function create(): View
    {
        return view('admin::app.plans.create', $this->formOptions());
    }

    public function store(StorePlanRequest $request, CreatePlan $createPlan): RedirectResponse|JsonResponse
    {
        $createPlan->handle($request->toDTO());

        if ($request->expectsJson()) {
            return toJsonWithSaveFlashMessage([
                'status' => 'success',
                'message' => 'Plan created successfully',
            ], Response::HTTP_CREATED);
        }

        return toUrlWithSaveMessage(route('admin.plans.index'), 'Plan created successfully');
    }

    public function show(Plan $plan): View
    {
        $plan->load(['currency', 'planFeatures.feature']);

        return view('admin::app.plans.show', compact('plan'));
    }

    public function edit(Plan $plan): View
    {
        $plan->load('planFeatures');

        return view('admin::app.plans.edit', [
            'plan' => $plan,
            ...$this->formOptions(),
        ]);
    }

    public function update(UpdatePlanRequest $request, Plan $plan, UpdatePlan $updatePlan): RedirectResponse|JsonResponse
    {
        $plan = $updatePlan->handle($plan, $request->toDTO());

        if ($request->expectsJson()) {
            return toJsonWithUpdateFlashMessage([
                'status' => 'success',
                'message' => 'Plan updated successfully',
            ]);
        }

        return toUrlWithUpdatedMessage(route('admin.plans.show', $plan), 'Plan updated successfully');
    }

    public function activate(Plan $plan, ChangePlanStatus $changeStatus): RedirectResponse|JsonResponse
    {
        $plan = $changeStatus->handle($plan, true);

        return $this->statusChangedResponse(request(), $plan, 'Plan activated successfully');
    }

    public function deactivate(Plan $plan, ChangePlanStatus $changeStatus): RedirectResponse|JsonResponse
    {
        $plan = $changeStatus->handle($plan, false);

        return $this->statusChangedResponse(request(), $plan, 'Plan deactivated successfully');
    }

    /**
     * @return array{currencies: Collection<int, Currency>, features: Collection<int, Feature>, billingIntervals: list<BillingInterval>}
     */
    private function formOptions(): array
    {
        return [
            'currencies' => Currency::queries()->activeOrderedByCode(),
            'features' => Feature::queries()->orderedForAssignment(),
            'billingIntervals' => BillingInterval::cases(),
        ];
    }

    private function statusChangedResponse(Request $request, Plan $plan, string $message): RedirectResponse|JsonResponse
    {
        if ($request->ajax() || $request->expectsJson()) {
            return toJsonWithUpdateFlashMessage([
                'status' => 'success',
                'message' => $message,
            ]);
        }

        return toUrlWithUpdatedMessage(route('admin.plans.show', $plan), $message);
    }
}

<?php

namespace DA\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use DA\Admin\DataTables\SubscriptionsDataTable;
use DA\Admin\Http\Requests\Subscription\AddAddonRequest;
use DA\Admin\Http\Requests\Subscription\CancelSubscriptionRequest;
use DA\Admin\Http\Requests\Subscription\ChangePlanRequest;
use DA\Admin\Http\Requests\Subscription\StoreSubscriptionRequest;
use DA\Admin\Http\Requests\Subscription\UpdateAddonQuantityRequest;
use DA\Admin\Http\Requests\Subscription\UpdateGraceDaysRequest;
use DA\Admin\Models\Addon;
use DA\Admin\Models\Plan;
use DA\Admin\Models\Subscription;
use DA\Admin\Models\Tenant;
use DA\Admin\Services\SubscriptionService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class SubscriptionController extends Controller
{
    public function index(SubscriptionsDataTable $dataTable)
    {
        return $dataTable->render('admin::app.subscriptions.index');
    }

    public function create(): View
    {
        return view('admin::app.subscriptions.create', $this->formOptions());
    }

    public function store(StoreSubscriptionRequest $request, SubscriptionService $subscriptions): RedirectResponse|JsonResponse
    {
        $subscription = $subscriptions->create($request->toDTO());

        if ($request->expectsJson()) {
            return toJsonWithSaveFlashMessage([
                'status' => 'success',
                'message' => 'Subscription created successfully',
            ], Response::HTTP_CREATED);
        }

        return toUrlWithSaveMessage(route('admin.subscriptions.show', $subscription), 'Subscription created successfully');
    }

    public function show(Subscription $subscription): View
    {
        $subscription->load([
            'tenant',
            'plan.currency',
            'items',
            'events',
        ]);

        $addonIds = $subscription->items
            ->filter(fn ($item) => $item->isAddon())
            ->pluck('reference_id')
            ->all();

        $addons = $addonIds === []
            ? collect()
            : Addon::query()->whereIn('id', $addonIds)->orderBy('name')->orderBy('id')->get()->keyBy('id');

        $availableAddons = Addon::queries()->activeOrderedByName()
            ->reject(fn (Addon $addon): bool => in_array($addon->id, $addonIds, true))
            ->values();

        return view('admin::app.subscriptions.show', [
            'subscription' => $subscription,
            'addons' => $addons,
            'availableAddons' => $availableAddons,
            'plans' => Plan::queries()->activeOrderedByName(),
        ]);
    }

    public function startTrial(Subscription $subscription, SubscriptionService $subscriptions): RedirectResponse|JsonResponse
    {
        $subscription = $subscriptions->startTrial($subscription);

        return $this->statusChangedResponse(request(), $subscription, 'Trial started successfully');
    }

    public function activate(Subscription $subscription, SubscriptionService $subscriptions): RedirectResponse|JsonResponse
    {
        $subscription = $subscriptions->activate($subscription);

        return $this->statusChangedResponse(request(), $subscription, 'Subscription activated successfully');
    }

    public function pause(Subscription $subscription, SubscriptionService $subscriptions): RedirectResponse|JsonResponse
    {
        $subscription = $subscriptions->pause($subscription);

        return $this->statusChangedResponse(request(), $subscription, 'Subscription paused successfully');
    }

    public function resume(Subscription $subscription, SubscriptionService $subscriptions): RedirectResponse|JsonResponse
    {
        $subscription = $subscriptions->resume($subscription);

        return $this->statusChangedResponse(request(), $subscription, 'Subscription resumed successfully');
    }

    public function cancel(CancelSubscriptionRequest $request, Subscription $subscription, SubscriptionService $subscriptions): RedirectResponse|JsonResponse
    {
        $subscription = $subscriptions->cancel($subscription, $request->cancelAtPeriodEnd());

        return $this->statusChangedResponse(request(), $subscription, 'Subscription cancellation recorded');
    }

    public function expire(Subscription $subscription, SubscriptionService $subscriptions): RedirectResponse|JsonResponse
    {
        $subscription = $subscriptions->expire($subscription);

        return $this->statusChangedResponse(request(), $subscription, 'Subscription expired successfully');
    }

    public function markPastDue(Subscription $subscription, SubscriptionService $subscriptions): RedirectResponse|JsonResponse
    {
        $subscription = $subscriptions->markPastDue($subscription);

        return $this->statusChangedResponse(request(), $subscription, 'Subscription marked past due');
    }

    public function changePlan(ChangePlanRequest $request, Subscription $subscription, SubscriptionService $subscriptions): RedirectResponse|JsonResponse
    {
        $plan = Plan::query()->findOrFail($request->planId());
        $subscription = $subscriptions->changePlan($subscription, $plan);

        return $this->statusChangedResponse(request(), $subscription, 'Subscription plan updated successfully');
    }

    public function updateGraceDays(UpdateGraceDaysRequest $request, Subscription $subscription, SubscriptionService $subscriptions): RedirectResponse|JsonResponse
    {
        $subscription = $subscriptions->updateGraceDays($subscription, $request->graceDays());

        return $this->statusChangedResponse(request(), $subscription, 'Grace window updated successfully');
    }

    public function addAddon(AddAddonRequest $request, Subscription $subscription, SubscriptionService $subscriptions): RedirectResponse|JsonResponse
    {
        $addon = Addon::query()->findOrFail($request->addonId());
        $subscription = $subscriptions->addAddon($subscription, $addon, $request->quantity());

        return $this->statusChangedResponse(request(), $subscription, 'Add-on added successfully');
    }

    public function updateAddonQuantity(UpdateAddonQuantityRequest $request, Subscription $subscription, Addon $addon, SubscriptionService $subscriptions): RedirectResponse|JsonResponse
    {
        $subscription = $subscriptions->updateAddonQuantity($subscription, $addon, $request->quantity());

        return $this->statusChangedResponse(request(), $subscription, 'Add-on quantity updated successfully');
    }

    public function removeAddon(Subscription $subscription, Addon $addon, SubscriptionService $subscriptions): RedirectResponse|JsonResponse
    {
        $subscription = $subscriptions->removeAddon($subscription, $addon);

        return $this->statusChangedResponse(request(), $subscription, 'Add-on removed successfully');
    }

    /**
     * @return array{tenants: Collection<int, Tenant>, plans: Collection<int, Plan>, addons: Collection<int, Addon>}
     */
    private function formOptions(): array
    {
        return [
            'tenants' => Tenant::queries()->activeOrderedByName(),
            'plans' => Plan::queries()->activeOrderedByName(),
            'addons' => Addon::queries()->activeOrderedByName(),
        ];
    }

    private function statusChangedResponse(Request $request, Subscription $subscription, string $message): RedirectResponse|JsonResponse
    {
        if ($request->ajax() || $request->expectsJson()) {
            return toJsonWithUpdateFlashMessage([
                'status' => 'success',
                'message' => $message,
            ]);
        }

        return toUrlWithUpdatedMessage(route('admin.subscriptions.show', $subscription), $message);
    }
}

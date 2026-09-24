<?php

namespace DA\Admin\Actions\Plan;

use DA\Admin\DTO\PlanDTO;
use DA\Admin\DTO\PlanFeatureAssignmentDTO;
use DA\Admin\Models\Plan;
use Illuminate\Support\Facades\DB;

class UpdatePlan
{
    public function handle(Plan $plan, PlanDTO $planDTO): Plan
    {
        return DB::transaction(function () use ($plan, $planDTO): Plan {
            $plan = Plan::queries()->findById($plan->id);

            $plan->name = $planDTO->name;
            $plan->code = $planDTO->code;
            $plan->description = $planDTO->description;
            $plan->billing_interval = $planDTO->billing_interval;
            $plan->price = $planDTO->price;
            $plan->currency_id = $planDTO->currency_id;
            $plan->trial_days = $planDTO->trial_days;
            $plan->is_active = $planDTO->is_active;
            $plan->save();

            $this->syncFeatures($plan, $planDTO->features);

            return $plan->refresh();
        });
    }

    /**
     * @param  list<PlanFeatureAssignmentDTO>  $features
     */
    private function syncFeatures(Plan $plan, array $features): void
    {
        $featureIds = collect($features)->pluck('feature_id')->all();

        $plan->planFeatures()
            ->whereNotIn('feature_id', $featureIds === [] ? [0] : $featureIds)
            ->delete();

        foreach ($features as $assignment) {
            $plan->planFeatures()->updateOrCreate(
                ['feature_id' => $assignment->feature_id],
                [
                    'value' => $assignment->value,
                    'is_unlimited' => $assignment->is_unlimited,
                ],
            );
        }
    }
}

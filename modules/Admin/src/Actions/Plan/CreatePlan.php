<?php

namespace DA\Admin\Actions\Plan;

use DA\Admin\DTO\PlanDTO;
use DA\Admin\DTO\PlanFeatureAssignmentDTO;
use DA\Admin\Models\Plan;
use Illuminate\Support\Facades\DB;

class CreatePlan
{
    public function handle(PlanDTO $planDTO): Plan
    {
        return DB::transaction(function () use ($planDTO): Plan {
            $plan = new Plan;
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
        foreach ($features as $assignment) {
            $plan->planFeatures()->create([
                'feature_id' => $assignment->feature_id,
                'value' => $assignment->value,
                'is_unlimited' => $assignment->is_unlimited,
            ]);
        }
    }
}

<?php

namespace DA\Admin\Actions\Plan;

use DA\Admin\DTO\PlanDTO;
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
            $plan->features = $planDTO->features['module'];
            $plan->limit_features = $planDTO->features['limit'];
            $plan->save();

            return $plan->refresh();
        });
    }
}

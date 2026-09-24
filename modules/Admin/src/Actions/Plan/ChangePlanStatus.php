<?php

namespace DA\Admin\Actions\Plan;

use DA\Admin\Models\Plan;

class ChangePlanStatus
{
    public function handle(Plan $plan, bool $isActive): Plan
    {
        $plan->is_active = $isActive;
        $plan->save();

        return $plan->refresh();
    }
}

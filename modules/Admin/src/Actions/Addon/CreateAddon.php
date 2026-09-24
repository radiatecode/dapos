<?php

namespace DA\Admin\Actions\Addon;

use DA\Admin\DTO\AddonDTO;
use DA\Admin\Models\Addon;

class CreateAddon
{
    public function handle(AddonDTO $addonDTO): Addon
    {
        $addon = new Addon;
        $addon->name = $addonDTO->name;
        $addon->code = $addonDTO->code;
        $addon->description = $addonDTO->description;
        $addon->billing_interval = $addonDTO->billing_interval;
        $addon->price = $addonDTO->price;
        $addon->currency_id = $addonDTO->currency_id;
        $addon->is_active = $addonDTO->is_active;
        $addon->save();

        return $addon;
    }
}

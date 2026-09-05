<?php

namespace DA\Admin\Actions\Addon;

use DA\Admin\DTO\AddonDTO;
use DA\Admin\Models\Addon;

class UpdateAddon
{
    public function handle(Addon $addon, AddonDTO $addonDTO): Addon
    {
        $addon = Addon::queries()->findById($addon->id);

        $addon->name = $addonDTO->name;
        $addon->code = $addonDTO->code;
        $addon->description = $addonDTO->description;
        $addon->billing_interval = $addonDTO->billing_interval;
        $addon->price = $addonDTO->price;
        $addon->currency_id = $addonDTO->currency_id;
        $addon->is_active = $addonDTO->is_active;
        $addon->save();

        return $addon->refresh();
    }
}

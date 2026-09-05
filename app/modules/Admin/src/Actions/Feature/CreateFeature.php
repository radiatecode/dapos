<?php

namespace DA\Admin\Actions\Feature;

use DA\Admin\DTO\FeatureDTO;
use DA\Admin\Models\Feature;

class CreateFeature
{
    public function handle(FeatureDTO $featureDTO): Feature
    {
        $feature = new Feature;
        $feature->name = $featureDTO->name;
        $feature->code = $featureDTO->code;
        $feature->type = $featureDTO->type;
        $feature->description = $featureDTO->description;
        $feature->save();

        return $feature;
    }
}

<?php

namespace DA\Admin\Actions\Feature;

use DA\Admin\DTO\FeatureDTO;
use DA\Admin\Models\Feature;

class UpdateFeature
{
    public function handle(Feature $feature, FeatureDTO $featureDTO): Feature
    {
        $feature = Feature::queries()->findById($feature->id);

        $feature->name = $featureDTO->name;
        $feature->code = $featureDTO->code;
        $feature->type = $featureDTO->type;
        $feature->description = $featureDTO->description;
        $feature->save();

        return $feature->refresh();
    }
}

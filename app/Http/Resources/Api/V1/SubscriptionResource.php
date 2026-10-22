<?php

namespace App\Http\Resources\Api\V1;

use DA\Admin\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Subscription
 */
class SubscriptionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'features' => $this->featureMap(),
        ];
    }

    /**
     * @return array<string, array{enabled: bool, type: string, value: string|null, unlimited: bool}>
     */
    private function featureMap(): array
    {
        $features = [];

        foreach ($this->limit_features ?? [] as $row) {
            $features[] = $row['feature'];
        }

        return array_merge($features, $this->features ?? []);
    }
}

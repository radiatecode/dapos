<?php

namespace DA\Admin\Actions\Tenant;

use DA\Admin\Actions\Default\CreateDefaultRole;
use DA\Admin\Actions\Default\CreateDefaultUnit;
use DA\Admin\DTO\TenantDefaultData;
use DA\Admin\Enums\DefaultDataType;
use DA\Admin\Models\Tenant;
use Illuminate\Support\Facades\DB;

class CreateDefaultData
{
    public function handle(int $id, TenantDefaultData $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $tenant = Tenant::queries()->findById($id);

            $submittedDefaultData = array_keys(array_filter($data->toArray()));

            $this->generateDefaultData($submittedDefaultData, $id);

            // store which default data is created in the tenant default_data json column
            $tenant->default_data = json_encode(array_merge(json_decode($tenant->default_data, true) ?? [], $submittedDefaultData));

            $tenant->save();

            return $tenant;
        });
    }

    public function generateDefaultData(array $defaultData, int $tenantId)
    {
        foreach ($defaultData as $data) {
            if ($data == DefaultDataType::DEFAULT_ROLES->value) {
                (new CreateDefaultRole)->handle($tenantId);
            }
            if ($data == DefaultDataType::DEFAULT_UNIT->value) {
                (new CreateDefaultUnit)->handle($tenantId);
            }
        }
    }
}

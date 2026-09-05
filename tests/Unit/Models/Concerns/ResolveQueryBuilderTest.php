<?php

use DA\Admin\Models\Queries\TenantQueries;
use DA\Admin\Models\Tenant;
use Tests\TestCase;

uses(TestCase::class);

it('resolves the tenant model from the query class name', function () {
    $query = (new TenantQueries)->datatable();

    expect($query->from)->toBe('tenants');
});

it('uses the given model class when one is provided', function () {
    $query = Tenant::queries()->datatable();

    expect($query->from)->toBe('tenants');
});

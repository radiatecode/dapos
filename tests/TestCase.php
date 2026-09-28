<?php

namespace Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Schema;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    protected function afterRefreshingDatabase()
    {
        if (Schema::hasTable('tenant_owned_items')) {
            return;
        }

        $connection = $this->app->make('db')->connection();

        // CREATE TABLE implicitly commits on MySQL and drops the wrapping test transaction.
        $connection->rollBack();

        Schema::create('tenant_owned_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id');
            $table->string('title');
            $table->timestamps();
        });

        $connection->beginTransaction();
    }
}

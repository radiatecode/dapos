<?php

use DA\Admin\Enums\SubscriptionStatus;
use DA\Admin\Models\Subscription;
use Illuminate\Support\Facades\Notification;

it('processes ended periods through the artisan command', function () {
    Notification::fake();

    $this->freezeTime();

    $subscription = Subscription::factory()->create([
        'grace_days' => 7,
        'current_period_end' => now()->subMinute(),
    ]);

    $this->artisan('subscriptions:process-periods')
        ->assertSuccessful()
        ->expectsOutputToContain('Grace started: 1');

    expect($subscription->refresh()->status)->toBe(SubscriptionStatus::PastDue);

    $this->travel(8)->days();

    $this->artisan('subscriptions:process-periods')
        ->assertSuccessful()
        ->expectsOutputToContain('Concluded: 1');

    expect($subscription->refresh()->status)->toBe(SubscriptionStatus::Expired);
});

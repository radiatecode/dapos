<?php

namespace DA\Admin\Notifications;

use DA\Admin\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionGraceStarted extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Subscription $subscription)
    {
        $this->afterCommit();
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->subscription->loadMissing(['tenant', 'plan']);

        $planName = $this->subscription->plan?->name ?? 'your plan';
        $tenantName = $this->subscription->tenant?->name ?? 'there';
        $graceEnds = $this->subscription->grace_ends_at?->toDayDateTimeString() ?? 'the end of the grace period';

        return (new MailMessage)
            ->subject('Your '.$planName.' subscription is in a grace period')
            ->greeting('Hello '.$tenantName)
            ->line('The current billing period for this subscription has ended.')
            ->line('Access continues during a grace window until '.$graceEnds.'.')
            ->line('Please renew or contact support before the grace period ends. After that, the subscription will expire or cancel.');
    }
}

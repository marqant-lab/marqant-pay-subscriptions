<?php

namespace Marqant\MarqantPaySubscriptions\Contacts;

use Illuminate\Database\Eloquent\Model;

abstract class SubscriptionsHandlerContract
{
    /**
     * Subscribe a given Billable to a plan on the payment provider side.
     *
     * @param \Illuminate\Database\Eloquent\Model $Billable
     * @param \Illuminate\Database\Eloquent\Model $Plan
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public abstract function subscribe(Model $Billable, Model $Plan): Model;

    /**
     * Run the billing cycle for all billables that are subscribed to a plan that is not managed through the payment
     * providers, but through a custom handler.
     *
     * @param null|string $cycle
     *
     * @return void
     */
    public abstract function runBillingCycle($cycle = null): void;
}
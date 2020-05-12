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
    public abstract function subscribe(Model &$Billable, Model $Plan): Model;
}
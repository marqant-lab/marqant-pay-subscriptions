<?php

namespace Marqant\MarqantPaySubscriptions\Services;

use Illuminate\Database\Eloquent\Model;
use Marqant\MarqantPaySubscriptions\Contacts\SubscriptionsHandlerContract;

class SubscriptionsHandler extends SubscriptionsHandlerContract
{
    /**
     * Subscribe a given Billable without payment provider.
     *
     * @param \Illuminate\Database\Eloquent\Model $Billable
     * @param \Illuminate\Database\Eloquent\Model $Plan
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function subscribe(Model &$Billable, Model $Plan): Model
    {
        /**
         * @var \App\User $Billable
         */

        // create local subscription with data from stripe
        $Billable->subscriptions()
            ->create([
                'plan_id' => $Plan->id,
            ]);

        // return the billable
        return $Billable;
    }
}
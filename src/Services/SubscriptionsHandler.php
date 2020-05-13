<?php

namespace Marqant\MarqantPaySubscriptions\Services;

use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Marqant\MarqantPaySubscriptions\Contracts\BillingCycleContract;
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

    /**
     * Run the billing cycle for all billables that are subscribed to a plan that is not managed through the payment
     * providers, but through a custom handler.
     *
     * @param null|string $cycle
     *
     * @return void
     * @throws \Exception
     */
    public function runBillingCycle($cycle = null): void
    {
        // get the supported billing cylces
        $BillingCycles = $this->getBillingCycles();

        // check if we should trigger a specific cycle
        if ($cycle) {
            // check if we actually have that billing cycle set up
            if (!$BillingCycles->has($cycle)) {
                throw new \Exception("The {$cycle} billing cycle is not set up.");
            }

            // get the billing cycle from the collection and trigger the handle method
            $BillingCycles->get($cycle)
                ->handle();

            // exit
            return;
        }

        // charge the actual billables for the next period
        $BillingCycles->each(function (\Marqant\MarqantPaySubscriptions\Contacts\BillingCycleContract $Cycle) {
            $Cycle->handle();
        });
    }

    /**
     * Get all billing cycles from the config as class instances.
     *
     * @return \Illuminate\Support\Collection
     */
    private function getBillingCycles(): Collection
    {
        return collect(config('marqant-pay-subscriptions.billing_cycles'))->map(function ($billing_cycle) {
            return app($billing_cycle);
        });
    }
}
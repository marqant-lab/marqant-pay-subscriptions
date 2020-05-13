<?php

namespace Marqant\MarqantPaySubscriptions\Tests\Models;

use Marqant\MarqantPaySubscriptions\Models\Plan;
use Marqant\MarqantPaySubscriptions\Tests\MarqantPaySubscriptionsTestCase;

class PlanTest extends MarqantPaySubscriptionsTestCase
{
    /**
     * Test if we can reach the billables of a plan.
     *
     * @test
     *
     * @return void
     *
     * @throws \Exception
     */
    public function test_access_billables_of_plan(): void
    {
        /**
         * @var \App\User $Billable
         */

        // set a custom subscription handler
        $SubscriptionHandler = \Marqant\MarqantPaySubscriptions\Services\SubscriptionsHandler::class;

        // set a custom subscription handler in config
        config([
            'marqant-pay-subscriptions.subscription_handler' => $SubscriptionHandler,
        ]);

        // get a plan
        $Plan = Plan::firstOrFail();

        // get billable
        $Billable = $this->createBillableUser();

        // subscribe billable to plan
        $Billable->subscribe($Plan);

        // assert that we have access to the users from the plan
        $this->assertCount(1, $Plan->users);
    }
}
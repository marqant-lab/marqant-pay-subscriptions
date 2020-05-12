<?php

namespace Marqant\MarqantPaySubscriptions\Tests\Services;

use Marqant\MarqantPaySubscriptions\Tests\MarqantPaySubscriptionsTestCase;

class SubscriptionsHandlerTest extends MarqantPaySubscriptionsTestCase
{
    /**
     * Setup up the test.
     */
    public function setUp(): void
    {
        parent::setUp();

        // set a custom subscription handler
        $SubscriptionHandler = \Marqant\MarqantPaySubscriptions\Services\SubscriptionsHandler::class;

        // set a custom subscription handler in config
        config([
            'marqant-pay-subscriptions.subscription_handler' => $SubscriptionHandler,
        ]);
    }

    /**
     * Test if we can creat a plan, without creating a plan on the provider end.
     *
     * @test
     *
     * @return void
     * @throws \Exception
     */
    public function test_create_plan_without_provider(): void
    {
        /**
         * @var \Marqant\MarqantPaySubscriptions\Models\Plan $Plan
         */
        $SubscriptionHandler = \Marqant\MarqantPaySubscriptions\Services\SubscriptionsHandler::class;

        // assert that the subscription handler in the current config is actually the one we just set
        $this->assertEquals($SubscriptionHandler, config('marqant-pay-subscriptions.subscription_handler'));

        // create plan model
        $Plan = $this->createPlanModel();

        // call the method that creates a plan at the provider
        // if the config is set up correctly, nothing should happen
        $Plan->createPlan();

        // assert that the plan is created in our database
        $this->assertNotNull($Plan->id);

        // assert that the plan has no stripe attributes
        $this->assertNull($Plan->stripe_id);
        $this->assertNull($Plan->stripe_product);
    }

    /**
     * Test if we can subscribe a user to a plan without needing the provider.
     *
     * @test
     *
     * @return void
     *
     * @throws \Exception
     */
    public function test_subscribe_billable_without_provider(): void
    {
        /**
         * @var \Marqant\MarqantPaySubscriptions\Models\Plan $Plan
         * @var \App\User                                    $Billable
         */
        $SubscriptionHandler = \Marqant\MarqantPaySubscriptions\Services\SubscriptionsHandler::class;

        // assert that the subscription handler in the current config is actually the one we just set
        $this->assertEquals($SubscriptionHandler, config('marqant-pay-subscriptions.subscription_handler'));

        // create plan model
        $Plan = $this->createPlanModel();

        // assert that the plan is created in our database
        $this->assertNotNull($Plan->id);

        // assert that the plan has no stripe attributes
        $this->assertNull($Plan->stripe_id);
        $this->assertNull($Plan->stripe_product);

        // get billable
        $Billable = $this->createBillableUser();

        // subscribe billable to plan with given provider
        $Billable->subscribe($Plan->slug);

        // assert that billable is subscribed in our database
        $this->assertCount(1, $Billable->subscriptions);

        // assert that all values needed are stored in the database and valid
        $Subscription = $Billable->subscriptions->first();
        $this->assertEmpty($Subscription->stripe_id);
        $this->assertEquals($Billable->id, $Subscription->billable_id);
        $this->assertEquals($Plan->id, $Subscription->plan_id);
    }
}
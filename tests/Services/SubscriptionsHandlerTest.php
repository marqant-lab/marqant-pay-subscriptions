<?php

namespace Marqant\MarqantPaySubscriptions\Tests\Services;

use Illuminate\Support\Carbon;
use Marqant\MarqantPay\Services\MarqantPay;
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

    /**
     * Test if we can run a billing cycle on the subscriptions, using the subscriptions handler class of this package.
     *
     * @test
     *
     * @return void
     *
     * @throws \Exception
     */
    public function test_run_monthly_billing_cycle_without_provider(): void
    {
        /**
         * @var \Marqant\MarqantPaySubscriptions\Models\Plan $Plan
         * @var \App\User                                    $Billable
         */
        $SubscriptionHandler = \Marqant\MarqantPaySubscriptions\Services\SubscriptionsHandler::class;

        // assert that the subscription handler in the current config is actually the one we just set
        $this->assertEquals($SubscriptionHandler, config('marqant-pay-subscriptions.subscription_handler'));

        // get monthly plan (comes from the seeders)
        $Plan = app(config('marqant-pay-subscriptions.plan_model'))
            ->where('type', 'monthly')
            ->firstOrFail();

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
        $this->assertCount(1, $Plan->subscriptions()
            ->where('billable_id', $Billable->id)
            ->get());

        // assert that all values needed are stored in the database and valid
        $Subscription = $Billable->subscriptions->first();
        $this->assertEmpty($Subscription->stripe_id);
        $this->assertEquals($Billable->id, $Subscription->billable_id);
        $this->assertEquals($Plan->id, $Subscription->plan_id);

        // now that we have a subscribed billable where everything is set up correctly,
        // we can try to start a billing cycle through the subscription handler in this package
        MarqantPay::runBillingCycle('monthly');

        // refresh subscription
        $Subscription->refresh();

        // assert that the subscription was charged, by checking if the last_charged
        // attribute is set to today (and not null, and a carbon instance 😉)
        $this->assertNotNull($Subscription->last_charged);
        $this->assertStringStartsWith(Carbon::now()
            ->format('Y-m-d H:i:'), $Subscription->last_charged);

        // assert that there is only one payment made by the billable
        $this->assertCount(1, $Billable->payments);

        // assert that the payment is succeeded
        $this->assertEquals('succeeded', $Billable->payments()
            ->first()->status);

        // assert that the resulting payment it marked as a subscription charge
        $this->assertTrue(!!$Billable->payments->first()->subscription);
    }
}
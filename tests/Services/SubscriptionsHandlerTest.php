<?php

namespace Marqant\MarqantPaySubscriptions\Tests\Services;

use DB;
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

        // Update config so we have the marqant-pay.invoice_service
        // setting set to the PdfInvoice service
        $PdfInvoiceService = \Marqant\MarqantPayInvoices\Services\PdfInvoice::class;
        config(['marqant-pay.invoice_service' => $PdfInvoiceService]);

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

        // update subscription
        $date = Carbon::now()
            ->subDays(config('marqant-pay-subscriptions.days_before_charge'))
            ->subDay();
        $Billable->subscriptions->first()
            ->update(['created_at' => $date]);

        // assert that billable is subscribed in our database
        $this->assertCount(1, $Billable->subscriptions);
        $this->assertCount(1, $Plan->subscriptions()
            ->chargeable()
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
            ->format('Y-m-d'), $Subscription->last_charged);

        // assert that there is only one payment made by the billable
        $this->assertCount(1, $Billable->payments);

        // assert that the payment is succeeded
        $this->assertEquals('succeeded', $Billable->payments()
            ->first()->status);

        // assert that the resulting payment it marked as a subscription charge
        $this->assertTrue(!!$Billable->payments->first()->subscription);

        // assert that we can create an invoice for subscriptions
        $Billable->payments->first()
            ->createInvoice();
        $this->assertNotNull($Billable->payments->first()->invoice);
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
    public function test_run_monthly_billing_cycle_without_provider_and_with_no_subscriptions_older_than_n_days(): void
    {
        /**
         * @var \Marqant\MarqantPaySubscriptions\Models\Plan $Plan
         * @var \App\User                                    $Billable
         */

        // Update config so we have the marqant-pay.invoice_service
        // setting set to the PdfInvoice service
        $PdfInvoiceService = \Marqant\MarqantPayInvoices\Services\PdfInvoice::class;
        config(['marqant-pay.invoice_service' => $PdfInvoiceService]);

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

        // delete all subscriptions
        DB::table('billable_plan')
            ->delete();

        // subscribe billable to plan with given provider
        $Billable->subscribe($Plan->slug);
        $Billable->refresh();

        // assert that billable is subscribed in our database
        $this->assertCount(1, $Billable->subscriptions);
        $this->assertCount(1, $Plan->subscriptions()
            ->where('billable_id', $Billable->id)
            ->get());

        // assert that the subscription is not old enough to be charged
        $this->assertCount(0, $Plan->subscriptions()
            ->chargeable()
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
        $this->assertNull($Subscription->last_charged);
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
    public function test_unsubscribe_billable_without_provider(): void
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

        /////////////////////////////////////////////////////////////
        // now that we have veryfied that the billable is signed up
        // and everything, we can unsubscribe him

        // unsubscribe the billable
        $Billable->unsubscribe($Plan->slug);

        // verify that there is no longer a subscription available
        $Subscription = $Billable->subscriptions->first();
        $this->assertNull($Subscription);
    }
}
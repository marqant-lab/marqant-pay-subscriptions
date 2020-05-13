<?php

/*
 |--------------------------------------------------------------------------
 | Marqant Pay Subscriptions Configuration
 |--------------------------------------------------------------------------
 |
 | In this configuration file you can set up all options regarding the
 | subscriptions via marqant/marqant-pay package.
 |
 */

return [

    /*
     |--------------------------------------------------------------------------
     | Plan Model
     |--------------------------------------------------------------------------
     |
     | This is the model used as representation of the plans at your payment
     | provider. Plans also are managed through the gateways.
     |
     */

    'plan_model' => \Marqant\MarqantPaySubscriptions\Models\Plan::class,

    /*
     |--------------------------------------------------------------------------
     | Subscription Model
     |--------------------------------------------------------------------------
     |
     | This is the model used as representation of the subscriptions at your
     | payment provider. Subscriptions as well are managed mostly through the
     | gateways.
     |
     */

    'subscription_model' => \Marqant\MarqantPaySubscriptions\Models\Subscription::class,

    /*
     |--------------------------------------------------------------------------
     | Subscription Handling
     |--------------------------------------------------------------------------
     |
     | In this section you can configure the handling of subscriptions. There
     | are two ways marqant-pay can handle subscriptions for you.
     |
     | The first option is to use the subscriptions and plans from the
     | providers. This is perfect for small projects, that only require one
     | payment provider and no custom invoices.
     |
     | The second option is to add your own handler in this section. It has to
     | implement the SubscriptionHandlerContract of the
     | marqant-pay-subscriptions package and provide the needed methods to
     | handle your logic. In this scenario, you also have setup a schedule for
     | your charges.
     |
     */

    'subscription_handler' => null,

    /*
     |--------------------------------------------------------------------------
     | Billing Cycles
     |--------------------------------------------------------------------------
     |
     | If you are using a custom subscription handler you have to manage your
     | the billing cycles of your app on your own. Each billing cycle has it's
     | own handler class which will be called through the subscription handler.
     | To add new billing cycles you just have to add your own handler in here.
     |
     */

    'billing_cycles' => [
        'monthly' => \Marqant\MarqantPaySubscriptions\BillingCycles\Monthly::class,
    ],

];

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

];

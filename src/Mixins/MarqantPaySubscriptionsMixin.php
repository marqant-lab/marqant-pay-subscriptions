<?php

namespace Marqant\MarqantPaySubscriptions\Mixins;

use Illuminate\Database\Eloquent\Model;

/**
 * @mixin \Marqant\MarqantPay\Services\MarqantPay
 */
class MarqantPaySubscriptionsMixin
{
    /**
     * Create a subscription for a billable on a given provider.
     *
     * @return \Closure
     */
    public static function subscribe(): \Closure
    {
        /**
         *
         * @param \Illuminate\Database\Eloquent\Model $Billable
         * @param string                              $plan
         *
         * @return \Illuminate\Database\Eloquent\Model
         */
        return function (Model $Billable, string $plan) {
            $Gateway = self::resolveProviderGateway($Billable);

            $Plan = app(config('marqant-pay-subscriptions.plan_model'))
                ->where('slug', $plan)
                ->firstOrFail();

            $Gateway->subscribe($Billable, $Plan);

            return $Billable;
        };
    }

    /**
     * Create a plan either on the provider or just in our database.
     *
     * @return \Closure
     */
    public static function createPlan(): \Closure
    {
        /**
         * Create the plan on the provider end.
         *
         * @param \Illuminate\Database\Eloquent\Model $Plan
         * @param string                              $provider
         *
         * @return \Illuminate\Database\Eloquent\Model
         * @throws \Exception
         */
        return function (Model $Plan, string $provider): Model {
            $ProviderGateway = self::resolveProviderGatewayFromString($provider);

            return $ProviderGateway->createPlan($Plan);
        };
    }
}
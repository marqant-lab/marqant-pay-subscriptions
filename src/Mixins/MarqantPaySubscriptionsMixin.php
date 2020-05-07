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
}
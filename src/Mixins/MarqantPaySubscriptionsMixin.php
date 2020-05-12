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
         * @param                                     $Plan
         *
         * @return \Illuminate\Database\Eloquent\Model
         */
        return function (Model $Billable, $Plan) {
            // if the setup uses a custom subscription handler, use
            // that instead of the one from the payment provider.
            $Gateway = config('marqant-pay-subscriptions.subscription_handler', false);
            if ($Gateway) {
                $Gateway = app($Gateway);
            }

            // if the setup doesn't have a custom subscription provider, make
            // use of the provider logic.
            if (!$Gateway) {
                $Gateway = self::resolveProviderGateway($Billable);
            }

            // resolve plan from string
            $Plan = self::resolvePlan($Plan);

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
        return function (Model $Plan, string $provider = null): Model {
            // if the setup dosn't use a custom subscription handler, we have to call the provider
            if ($provider && !config('marqant-pay-subscriptions.subscription_handler', false)) {
                $ProviderGateway = self::resolveProviderGatewayFromString($provider);

                $ProviderGateway->createPlan($Plan);
            }

            return $Plan;
        };
    }

    /**
     * Resolve a plan from a string, or if it is already an object, ensure that it has the right type.
     *
     * @return \Closure
     */
    public static function resolvePlan(): \Closure
    {
        /**
         * @param $Plan
         *
         * @return \Illuminate\Contracts\Foundation\Application|mixed
         * @throws \Exception
         */
        return function ($Plan) {
            // get the plan model from config
            $PlanModel = app(config('marqant-pay-subscriptions.plan_model'));

            // resolve the plan
            if (is_string($Plan)) {
                $Plan = $PlanModel->where('slug', $Plan)
                    ->firstOrFail();
            }

            // ensure that the plan is an instance of the plan model from the config
            if (!$Plan instanceof $PlanModel) {
                throw new \Exception('plan needs to be a string or an instance of Plan.');
            }

            return $Plan;
        };
    }

}
<?php

namespace Marqant\MarqantPaySubscriptions\Mixins;

use Illuminate\Database\Eloquent\Model;
use Marqant\MarqantPay\Services\MarqantPay;
use Marqant\MarqantPay\Contracts\PaymentMethodContract;

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
            /**
             * @var \App\User $Billable
             */

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

            // get subscription of already signed up
            $Subscription = $Billable->subscriptions()
                ->whereHas('plan', function ($query) use ($Plan) {
                    $query->where('slug', $Plan->slug);
                })
                ->first();
            if ($Subscription) {
                return $Billable;
            }

            // only subscribe the billable entity if not assigned yet
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

            // try to restore the plan object if possible
            $data = json_decode($Plan, true);
            if ($data) {
                $Plan = $PlanModel::findOrFail($data['id']);
            }

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

    /**
     * Expose runBillingCycle method to MarqantPay service through this mixin.
     *
     * @return \Closure
     */
    public static function runBillingCycle(): \Closure
    {
        /**
         * Run the billing cycle for all billables that are subscribed to a plan that is not managed through the payment
         * providers, but through a custom handler.
         *
         * @param null|string $cycle
         *
         * @return void
         */
        return function ($cycle = null) {
            /**
             * @var \Marqant\MarqantPaySubscriptions\Services\SubscriptionsHandler $SubscriptionHandler
             */

            // get subscription handler from config
            $SubscriptionHandler = config('marqant-pay-subscriptions.subscription_handler', false);
            if (!$SubscriptionHandler) {
                throw new \Exception('No subscription handler set up.');
            }
            $SubscriptionHandler = app($SubscriptionHandler);

            // run the actual method on the subscription handler
            $SubscriptionHandler->runBillingCycle($cycle);
        };
    }

    /**
     * Expose chargeSubscription method on MarqantPay service.
     *
     * @return \Closure
     */
    public static function chargeSubscription(): \Closure
    {
        /**
         * Charge a subscription and set the subscription flag to true in database.
         *
         * @param \Illuminate\Database\Eloquent\Model                      $Billable
         * @param float                                                    $amount
         * @param string                                                   $description
         * @param null|\Marqant\MarqantPay\Contracts\PaymentMethodContract $PaymentMethod
         *
         * @return \Illuminate\Database\Eloquent\Model
         *
         * @throws \Exception
         */
        return function (Model $Billable, float $amount, string $description,
                         ?PaymentMethodContract $PaymentMethod = null): Model {
            $ProviderGateway = self::resolveProviderGateway($Billable, $PaymentMethod);

            // execute normal charge
            $Payment = $ProviderGateway->charge($Billable, $amount, $description, $PaymentMethod);

            // set the subscription flag to true
            $Payment->update(['subscription' => true]);

            return $Payment;
        };
    }

    /**
     * Expose closure to cancel a subscription, that is not managed by a provider.
     *
     * @return \Closure
     */
    public static function unsubscribe(): \Closure
    {
        /**
         * Cancel a subscription.
         *
         * @param \Illuminate\Database\Eloquent\Model $Billable
         * @param                                     $Plan
         *
         * @return \Illuminate\Database\Eloquent\Model
         */
        return function (Model $Billable, $Plan): Model {
            /**
             * @var \App\User $Billable
             */

            // if the setup uses a custom subscription handler, use
            // that instead of the one from the payment provider.
            $Gateway = config('marqant-pay-subscriptions.subscription_handler', false);
            if ($Gateway) {
                $Gateway = app($Gateway);
            }

            $Plan = MarqantPay::resolvePlan($Plan);

            // if the setup doesn't have a custom subscription provider, make
            // use of the provider logic.
            if (!$Gateway) {
                $Gateway = self::resolveProviderGateway($Billable);
            }

            return $Gateway->unsubscribe($Billable, $Plan);
        };
    }

}
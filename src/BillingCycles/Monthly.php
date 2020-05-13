<?php

namespace Marqant\MarqantPaySubscriptions\BillingCycles;

use Illuminate\Database\Eloquent\Builder;
use Marqant\MarqantPay\Services\MarqantPay;
use Marqant\MarqantPaySubscriptions\Contacts\BillingCycleContract;

class Monthly extends BillingCycleContract
{
    /**
     * The billing cycle this class is responsible for.
     *
     * @var string
     */
    protected const BILLING_CYCLE = "monthly";

    /**
     * Handle method to call when this billing cycle is triggered.
     *
     * @return void
     *
     * @throws \Exception
     */
    protected function run(): void
    {
        // get the plans related to this billing cycle (monthly) and are not managed by a provider
        $Plans = app(config('marqant-pay-subscriptions.plan_model'))
            ->whereNull('provider') // we only do provider less subscriptions here
            ->where('type', self::BILLING_CYCLE)
            ->get();

        // get billable models from config
        $BillableModels = MarqantPay::getBillables();

        $BillablesByType = $BillableModels->map(function ($BillableModel) use ($Plans) {
            // only take billables that have a matching plan
            return $BillableModel->whereHas('subscriptions', function (Builder $query) use ($Plans) {
                $query->whereIn('plan_id', $Plans->pluck('id'));
            })
                ->get();
        });

        // ensure that we only have billables once in the billable types collection
        $BillablesByType = $BillablesByType->map(function ($Billables) {
            return $Billables->keyBy('id');
        });

        // loop through the billing types and create the payments for each billable (see below)
        $BillablesByType->each(function ($Billables) use ($Plans) {
            $Billables->each(function ($Billable) use ($Plans) {
                // TODO: Move the following into a dispatchable job

                // get subscriptions
                $SubscriptionsOfBillable = $Billable->subscriptions()
                    ->whereIn('plan_id', $Plans->pluck('id'))
                    ->get();

                // get the plans of this billable
                $PlansOfBillable = $Billable->plans()
                    ->whereIn('plan_id', $Plans->pluck('id'))
                    ->get();

                // sum the amounts of the plans related to this billable
                // TODO: make use of the methods from Dimons trait
                $amount = $PlansOfBillable->sum('amount');

                // get description from translation file
                $description = trans('marqant-pay-subscriptions::billing.description');

                // charge the billable once, with the total amount from all the plans he is subscribed to
                $Billable->charge($amount, $description);

                // touch the subscriptions
                $SubscriptionsOfBillable->each(function (\Illuminate\Database\Eloquent\Model $Subscription) {
                    /**
                     * @var \Marqant\MarqantPaySubscriptions\Models\Subscription $Subscription
                     */
                    $Subscription->touchLastCharged();
                });
            });
        });
    }
}
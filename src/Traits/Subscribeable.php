<?php

namespace Marqant\MarqantPaySubscriptions\Traits;

use Illuminate\Database\Eloquent\Model;
use Marqant\MarqantPay\Services\MarqantPay;
use Marqant\MarqantPay\Contracts\PaymentMethodContract;
use Marqant\MarqantPaySubscriptions\Models\Relationships\MorphManySubscriptions;

trait Subscribeable
{
    use MorphManySubscriptions;

    /**
     * Subscribe billable model to a plan.
     *
     * @param string $plan
     *
     * @return \Illuminate\Database\Eloquent\Model|$this
     */
    public function subscribe(string $plan): Model
    {
        return MarqantPay::subscribe($this, $plan);
    }

    /**
     * Charge the subscribeable/billable for a subscription.
     *
     * This method is used in the custom billing cycles.
     */
    public function chargeSubscription(int $amount, string $description,
                                       ?PaymentMethodContract $PaymentMethod = null): Model
    {
        return MarqantPay::chargeSubscription($this, $amount, $description, $PaymentMethod);
    }
}
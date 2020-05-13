<?php

namespace Marqant\MarqantPaySubscriptions\Traits;

use Illuminate\Database\Eloquent\Model;
use Marqant\MarqantPay\Services\MarqantPay;
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
}
<?php

namespace Marqant\MarqantPaySubscriptions\Traits;

use Str;
use Illuminate\Support\Carbon;
use Marqant\MarqantPay\Models\Relationships\BelongsToBillable;
use Marqant\MarqantPaySubscriptions\Models\Relationships\BelongsToPlan;

/**
 * Trait RepresentsSubscription
 *
 * @package Marqant\MarqantPay\Traits
 *
 * @mixin \Illuminate\Database\Eloquent\Relations\Pivot
 */
trait RepresentsSubscription
{
    use BelongsToPlan;
    use BelongsToBillable;

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * Get the table associated with the model.
     *
     * @return string
     */
    public function getTable()
    {
        $plan_model = config('marqant-pay-subscriptions.plan_model');

        $plan_table = app($plan_model)->getTable();

        $plan_singular = Str::singular($plan_table);

        return $this->table ?? "billable_{$plan_singular}";
    }

    /**
     * Touch the last_charged attribute on the subscription.
     *
     * @return bool
     */
    public function touchLastCharged(): bool
    {
        $this->last_charged = Carbon::now();

        return $this->save();
    }
}
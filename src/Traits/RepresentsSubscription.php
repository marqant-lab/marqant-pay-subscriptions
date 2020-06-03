<?php

namespace Marqant\MarqantPaySubscriptions\Traits;

use Str;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;
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

    /**
     * Scope to filter out subscriptions that are chargeable.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     *
     * @return \Illuminate\Database\Eloquent\Builder $query
     */
    public function scopeChargeable(Builder $query)
    {
        $n = config('marqant-pay-subscriptions.days_before_charge');

        $date = Carbon::now()
            ->startOfDay()
            ->subDays($n);

        return $query->where('created_at', '<', $date);
    }

    /**
     * Scope to filter out subscriptions that are not chargeable yet.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     *
     * @return \Illuminate\Database\Eloquent\Builder $query
     */
    public function scopeNotChargeable(Builder $query)
    {
        $n = config('marqant-pay-subscriptions.days_before_charge');

        $date = Carbon::now()
            ->startOfDay()
            ->subDays($n);

        return $query->where('created_at', '<', $date);
    }
}
<?php

namespace Marqant\MarqantPaySubscriptions\Models\Relationships;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Trait BelongsToPlan
 *
 * @package Marqant\MarqantPay\Models\Relationships
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait BelongsToPlan
{
    /**
     * Establishes a belongs to relationship with the plan model from the configuration.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function plan(): BelongsTo
    {
        $model = config('marqant-pay-subscriptions.plan_model');

        return $this->belongsTo($model);
    }
}
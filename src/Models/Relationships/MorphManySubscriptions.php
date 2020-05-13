<?php

namespace Marqant\MarqantPaySubscriptions\Models\Relationships;

use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Trait HasManySubscriptions
 *
 * @package Marqant\MarqantPaySubscriptions\Models\Relationships
 *
 * @mixin \Eloquent
 */
trait MorphManySubscriptions
{
    /**
     * Establishes a relationships with the subscription model from the config.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function subscriptions(): MorphMany
    {
        $model = config('marqant-pay-subscriptions.subscription_model');

        return $this->morphMany($model, 'billable');
    }
}
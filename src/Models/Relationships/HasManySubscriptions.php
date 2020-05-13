<?php

namespace Marqant\MarqantPaySubscriptions\Models\Relationships;

use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Trait HasManySubscriptions
 *
 * @package Marqant\MarqantPaySubscriptions\Models\Relationships
 *
 * @mixin \Eloquent
 */
trait HasManySubscriptions
{
    /**
     * Establishes a relationships with the subscription model from the config.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function subscriptions(): HasMany
    {
        $model = config('marqant-pay-subscriptions.subscription_model');

        return $this->hasMany($model);
    }
}
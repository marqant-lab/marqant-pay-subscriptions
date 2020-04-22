<?php

namespace Marqant\MarqantPaySubscriptions\Models\Relationships;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @mixin \Eloquent
 */
trait BelongsToManyPlans
{
    /**
     * Establishes a belongs to many relationship with the Plan model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany;
     */
    public function plans(): BelongsToMany
    {
        $model = config('marqant-pay-subscriptions.plan_model');

        return $this->belongsToMany($model);
    }
}
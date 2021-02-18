<?php

namespace Marqant\MarqantPaySubscriptions\Traits;

use Illuminate\Database\Eloquent\Model;
use Marqant\MarqantPay\Services\MarqantPay;
use Marqant\MarqantPay\Models\Relationships\BelongsToManyProviders;

trait RepresentsPlan
{
    use BelongsToManyProviders;

    /*
     |--------------------------------------------------------------------------
     | Payment Gateway Abstraction
     |--------------------------------------------------------------------------
     |
     | In this section you will find all proxy methods to the payment provider
     | gateway.
     |
     */

    /**
     * Create plan on the provider end.
     *
     * @param string $provider
     *
     * @return \Illuminate\Database\Eloquent\Model
     * @throws \Exception
     */
    public function createPlan(string $provider): Model
    {
        return MarqantPay::createPlan($this, $provider);
    }

    /*
     |--------------------------------------------------------------------------
     | Getters and Setters
     |--------------------------------------------------------------------------
     |
     | In this section you will find the default getters and setters of the plan
     | model.
     |
     */

    /**
     * Set the amount value. Take a float and store it as integer in the database.
     *
     * @param float $value
     *
     * @return void
     */
    public function setAmountAttribute(float $value): void
    {
        $this->attributes['amount'] = $value * 100;
    }

    /**
     * Get the raw amount from the database as integer.
     *
     * @return int
     */
    public function getAmountRawAttribute(): int
    {
        return $this->attributes['amount'];
    }

    /*
     |--------------------------------------------------------------------------
     | Scopes
     |--------------------------------------------------------------------------
     |
     | In this section you will find scopes that can be used on the model
     | representing the plans.
     |
     */

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('active', 1);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeNotActive($query)
    {
        return $query->where('active', 0);
    }
}

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
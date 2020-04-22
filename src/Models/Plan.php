<?php

namespace Marqant\MarqantPaySubscriptions\Models;

use Illuminate\Database\Eloquent\Model;
use Marqant\MarqantPaySubscriptions\Traits\RepresentsPlan;
use Marqant\MarqantPay\Models\Relationships\BelongsToManyProviders;

/**
 * Class Plan
 *
 * @mixin \Eloquent
 */
class Plan extends Model
{
    use RepresentsPlan;
    use BelongsToManyProviders;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];
}
<?php

namespace Marqant\MarqantPaySubscriptions\Models;

use Illuminate\Database\Eloquent\Relations\MorphPivot;
use Marqant\MarqantPaySubscriptions\Traits\RepresentsSubscription;

/**
 * Class Subscription
 *
 * @mixin \Eloquent
 */
class Subscription extends MorphPivot
{
    use RepresentsSubscription;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $casts = [
        'last_charged' => 'date',
    ];

}

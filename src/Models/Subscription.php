<?php

namespace Marqant\MarqantPaySubscriptions\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Marqant\MarqantPaySubscriptions\Traits\RepresentsSubscription;

/**
 * Class Subscription
 *
 * @mixin \Eloquent
 */
// TODO: Find out if I have to extend MorphPivot instead of Pivot to make the MorphTo relationships work. Otherwise
//       this should be fine.
class Subscription extends Pivot
{
    use RepresentsSubscription;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

}

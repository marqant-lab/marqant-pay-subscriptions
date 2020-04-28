<?php

namespace Marqant\MarqantPay\Models;

use Illuminate\Database\Eloquent\Model;
use Marqant\MarqantPay\Traits\RepresentsSubscription;

/**
 * Class Subscription
 *
 * @mixin \Eloquent
 */
class Subscription extends Model
{
    use RepresentsSubscription;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];
}

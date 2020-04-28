<?php

namespace Marqant\MarqantPaySubscriptions\Traits;

use Marqant\MarqantPay\Models\Relationships\BelongsToManyBillables;
use Marqant\MarqantPaySubscriptions\Models\Relationships\BelongsToPlan;

/**
 * Trait RepresentsSubscription
 *
 * @package Marqant\MarqantPay\Traits
 *
 * @mixin \Illuminate\Database\Eloquent\Relations\Pivot
 */
trait RepresentsSubscription
{
    use BelongsToPlan;
    use BelongsToManyBillables;

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;
}
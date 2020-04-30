<?php

namespace Marqant\MarqantPaySubscriptions\Models;

use Illuminate\Database\Eloquent\Model;
use Marqant\MarqantPaySubscriptions\Traits\RepresentsPlan;

/**
 * Class Plan
 *
 * @mixin \Eloquent
 */
class Plan extends Model
{
    use RepresentsPlan;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];
}

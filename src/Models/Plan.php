<?php

namespace Marqant\MarqantPaySubscriptions\Models;

use Illuminate\Database\Eloquent\Model;
use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Marqant\MarqantPaySubscriptions\Factories\PlanFactory;
use Marqant\MarqantPaySubscriptions\Traits\RepresentsPlan;

/**
 * Class Plan
 *
 * @mixin \Eloquent
 */
class Plan extends Model
{
    use RepresentsPlan;
    use HasFactory;
    use Sluggable;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return PlanFactory::new();
    }

    /**
     * Return the sluggable configuration array for this model.
     *
     * @return array
     */
    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'name'
            ]
        ];
    }
}

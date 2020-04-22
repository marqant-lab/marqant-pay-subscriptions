<?php

namespace Marqant\MarqantPaySubscriptions\Seeds;

use Illuminate\Database\Seeder;
use Marqant\MarqantPay\Models\Provider;
use Marqant\MarqantPaySubscriptions\Models\Plan;

class PlanProviderTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        /**
         * @var Provider $Stripe
         */

        // get plans
        $Monthly = Plan::where('slug', 'monthly')
            ->firstOrFail();
        $Yearly = Plan::where('slug', 'yearly')
            ->firstOrFail();
        // get stripe provider
        $Stripe = Provider::where('slug', 'stripe')
            ->firstOrFail();

        // connect them 😉
        $Stripe->plans()
            ->syncWithoutDetaching([
                $Monthly->id,
                $Yearly->id,
            ]);
    }
}
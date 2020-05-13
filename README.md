# Marqant Pay Subscriptions

This package is an extension of the [marqant-lab/marqant-pay](https://github.com/marqant-lab/marqant-pay) package and
 provides subscription functionality for it.

## Installation

To install this package you just need to run the good old composer command that you all know and love.

```shell script
composer require marqant-lab/marqant-pay-subscriptions 
```

Next you will need to create the migrations to hook this package up to your database. Make sure to replace the `User` 
 model with whatever you use as billable. The rest of the values will be taken from the configuration of this package.
 You can overwrite them if you want to.

```shell script
php artisan marqant-pay:migrations:subscriptions App\\User
# or
php artisan marqant-pay:migrations:subscriptions "App\\User"
```

Now you can run your migrations as usual to finish up the installation.

```shell script
php artisan migrate
```

And that's it, you have extended your project with subscriptions 🤯

## Usage

In larger applications for your clients, it will be very likely, that they want custom invoices and maybe custom
 billing cycles. But don't worry, marqant-pay got your back! This package enables you to add custom invoice PDF
  templates and
  custom Billing Cycles.
  
### Custom Invoice PDF

To overwrite the default template (which is really just a placeholder) you have to create your own PDF template as a
 blade file under `resources/views/vendor/marqant-pay-subscriptions/pdf/invoice.bade.php`.

### Custom Billing Cylces

If you need to add abilling cycle to your application, then you just need to add it to the configuration and crate a
 handler class for it.
 
```
    /*
     |--------------------------------------------------------------------------
     | Billing Cycles
     |--------------------------------------------------------------------------
     |
     | If you are using a custom subscription handler you have to manage your
     | the billing cycles of your app on your own. Each billing cycle has it's
     | own handler class which will be called through the subscription handler.
     | To add new billing cycles you just have to add your own handler in here.
     |
     */

    'billing_cycles' => [
        'monthly' => \Marqant\MarqantPaySubscriptions\BillingCycles\Monthly::class,
    ],
```

```php
<?php

namespace Marqant\MarqantPaySubscriptions\BillingCycles;

use Illuminate\Database\Eloquent\Builder;use Marqant\MarqantPay\Services\MarqantPay;use Marqant\MarqantPaySubscriptions\Contacts\BillingCycleContract;

class Monthly extends BillingCycleContract
{
    /**
     * The billing cycle this class is responsible for.
     *
     * @var string
     */
    protected const BILLING_CYCLE = "monthly";

    /**
     * Handle method to call when this billing cycle is triggered.
     *
     * @return void
     *
     * @throws \Exception
     */
    protected function run(): void
    {
        // get the plans related to this billing cycle (monthly) and are not managed by a provider
        $Plans = app(config('marqant-pay-subscriptions.plan_model'))
            ->whereNull('provider') // we only do provider less subscriptions here
            ->where('type', self::BILLING_CYCLE)
            ->get();

        // get billable models from config
        $BillableModels = MarqantPay::getBillables();

        $BillablesByType = $BillableModels->map(function ($BillableModel) use ($Plans) {
            // only take billables that have a matching plan
            return $BillableModel->whereHas('subscriptions', function (Builder $query) use ($Plans) {
                $query->whereIn('plan_id', $Plans->pluck('id'));
            })
                ->get();
        });

        // ensure that we only have billables once in the billable types collection
        $BillablesByType = $BillablesByType->map(function ($Billables) {
            return $Billables->keyBy('id');
        });

        // loop through the billing types and create the payments for each billable (see below)
        $BillablesByType->each(function ($Billables) use ($Plans) {
            $Billables->each(function ($Billable) use ($Plans) {
                // TODO: Move the following into a dispatchable job

                // get subscriptions
                $SubscriptionsOfBillable = $Billable->subscriptions()
                    ->whereIn('plan_id', $Plans->pluck('id'))
                    ->get();

                // get the plans of this billable
                $PlansOfBillable = $SubscriptionsOfBillable->map(function ($Subscription) {
                    return $Subscription->plan;
                })
                    ->keyBy('id');

                // sum the amounts of the plans related to this billable
                // TODO: make use of the methods from Dimons trait
                $amount = $PlansOfBillable->sum('amount');

                // get description from translation file
                $description = trans('marqant-pay-subscriptions::billing.description');

                // charge the billable once, with the total amount from all the plans he is subscribed to
                $Billable->charge($amount, $description);

                // touch the subscriptions
                $SubscriptionsOfBillable->each(function (\Illuminate\Database\Eloquent\Model $Subscription) {
                    /**
                     * @var \Marqant\MarqantPaySubscriptions\Models\Subscription $Subscription
                     */
                    $Subscription->touchLastCharged();
                });
            });
        });
    }
}
```
<?php

namespace Marqant\MarqantPaySubscriptions;

use Illuminate\Support\Str;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Model;
use Marqant\MarqantPay\Services\MarqantPay;
use Marqant\MarqantPaySubscriptions\Mixins\MarqantPaySubscriptionsMixin;
use Marqant\MarqantPaySubscriptions\Commands\MigrationsForSubscriptions;

class MarqantPaySubscriptionsServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     * @throws \ReflectionException
     */
    public function register()
    {
        $this->setupConfig();

        $this->setupMixins();
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // $this->setupMigrations();
        $this->setupCommands();

        $this->setupRelationships();

        $this->setupTranslations();
    }

    /**
     * Setup configuration in register method.
     *
     * @return void
     */
    private function setupConfig()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/marqant-pay-subscriptions.php', 'marqant-pay-subscriptions');
    }

    /**
     * Setup migrations in boot method.
     *
     * @return void
     */
    private function setupMigrations()
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    /**
     * Setup commands in boot method.
     *
     * @return void
     */
    private function setupCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                MigrationsForSubscriptions::class,
            ]);
        }
    }

    /**
     * Setup relationships in boot method.
     */
    private function setupRelationships()
    {
        $this->extendProviderModel();
        $this->extendPlanModel();
        $this->extendBillableModels();
    }

    /**
     * Setup mixins to extend the base-package through the Macroable trait in register method.
     *
     * @return void
     *
     * @throws \ReflectionException
     */
    private function setupMixins()
    {
        MarqantPay::mixin(app(MarqantPaySubscriptionsMixin::class));
    }

    /**
     * Setup the translations for this package.
     *
     * @return void
     *
     */
    private function setupTranslations()
    {
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'marqant-pay-subscriptions');
    }

    /**
     * Extend the Provider model from the config.
     *
     * @return void
     */
    private function extendProviderModel(): void
    {
        // extend Provider model
        $ProviderModel = config('marqant-pay.provider_model');
        $ProviderModel::addDynamicRelation('plans', function (Model $Provider) {
            return $Provider->belongsToMany(config('marqant-pay-subscriptions.plan_model'));
        });
    }

    /**
     * Extend the Plan model from the config.
     *
     * @return void
     */
    private function extendPlanModel(): void
    {
        // extend Plan model
        // - get the model from config
        // - loop through the billables of the config and add relationships to the Plan model
        $PlanModel = config('marqant-pay-subscriptions.plan_model');
        collect(config('marqant-pay.billables'))->each(function ($BillableModel, $method) use ($PlanModel) {
            /**
             * @var \Marqant\MarqantPaySubscriptions\Models\Plan $PlanModel
             */

            // get plural of key as method name
            $method = Str::plural($method);

            // add the relationship to the model
            $PlanModel::addDynamicRelation($method, function (Model $Plan) use ($BillableModel) {
                return $Plan->morphedByMany($BillableModel, 'billable', 'billable_plan')
                    ->using(config('marqant-pay-subscriptions.subscription_model'));
            });
        });
    }

    /**
     * Extend the billables from the config.
     *
     * @return void
     */
    private function extendBillableModels(): void
    {
        // extend Billables
        // - get plans model
        // - add plans relationship to billables
        $PlanModel = config('marqant-pay-subscriptions.plan_model');
        collect(config('marqant-pay.billables'))->each(function ($BillableModel) use ($PlanModel) {
            /**
             * @var \App\User $BillableModel
             */
            $BillableModel::addDynamicRelation('plans', function (Model $Billable) use ($PlanModel) {
                /**
                 * @var \App\User $Billable
                 */
                return $Billable->morphToMany($PlanModel, 'billable', 'billable_plan')
                    ->using(config('marqant-pay-subscriptions.subscription_model'));
            });
        });
    }
}
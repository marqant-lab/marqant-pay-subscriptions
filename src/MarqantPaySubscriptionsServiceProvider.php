<?php

namespace Marqant\MarqantPaySubscriptions;

use Illuminate\Support\ServiceProvider;
use Marqant\MarqantPay\Models\Provider;
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
        // extend Provider model
        Provider::addDynamicRelation('plans', function (Provider $model) {
            return $model->belongsToMany(config('marqant-pay-subscriptions.plan_model'));
        });
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
}
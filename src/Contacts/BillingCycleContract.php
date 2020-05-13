<?php

namespace Marqant\MarqantPaySubscriptions\Contacts;

abstract class BillingCycleContract
{
    /**
     * The billing cycle this class is responsible for.
     *
     * @var null|string
     */
    protected const BILLING_CYCLE = null;

    /**
     * The run method that needs to be defined on the billing cycle extending this contact.
     *
     * @return void
     *
     * @throws \Exception
     */
    abstract protected function run(): void;

    /**
     * Handle method to call when this billing cycle is triggered.
     *
     * @return void
     *
     * @throws \Exception
     */
    public function handle(): void
    {
        // check if the billing cycle is set up propperly
        $this->validateBillingCycle();

        // call the run method on the billing cycle
        $this->run();
    }

    /**
     * Check if the extending class is a valid billing cycle.
     *
     * @return void
     *
     * @throws \Exception
     */
    private function validateBillingCycle()
    {
        // assert that the billing cycle constant is set
        if (!static::BILLING_CYCLE) {
            $class = static::class;
            throw new \Exception("The BILLING_CYCLE is not set on the {$class}. It doesn't feel responsible for any billing cycles 🤷‍");
        }
    }
}
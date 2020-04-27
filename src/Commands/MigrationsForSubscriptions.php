<?php

namespace Marqant\MarqantPaySubscriptions\Commands;

use Str;
use File;
use Illuminate\Support\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\Finder\SplFileInfo;
use Marqant\MarqantPay\Traits\RepresentsProvider;
use Marqant\MarqantPaySubscriptions\Traits\RepresentsPlan;

class MigrationsForSubscriptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'marqant-pay:migrations:subscriptions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command will create the migrations needed for the subscriptions. The values are taken from the configuration of marqant-pay and marqant-pay-subscriptions.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        // create migrations for the plans
        $this->handlePlans();

        // create migrations for plan_provider relationship
        $this->handlePlanProvider();
    }

    /**
     * Method to handle te part for the plans.
     *
     * @return void
     */
    private function handlePlans(): void
    {
        $Plan = $this->getPlansModel();

        $this->makeMigrationForPlans($Plan);

        $this->info('Plans done! 👍');
    }

    /**
     * Method to handle the part for the plans and provider many to many relationship.
     *
     * @return void
     */
    private function handlePlanProvider(): void
    {
        $Plan = $this->getPlansModel();
        $Provider = $this->getProviderModel();

        $this->makeMigrationForPlanProvider($Plan, $Provider);

        $this->info('Plan Provider Relationship done too! 👍');
    }

    /**
     * Get Plan model from configuration.
     *
     * @return Model
     */
    private function getPlansModel()
    {
        $Plan = app(config('marqant-pay-subscriptions.plan_model'));

        $this->checkIfModelRepresentsPlan($Plan);

        return $Plan;
    }

    /**
     * Get Provider model from configuration.
     *
     * @return Model
     */
    private function getProviderModel()
    {
        $Provider = app(config('marqant-pay.provider_model'));

        $this->checkIfModelRepresentsProvider($Provider);

        return $Provider;
    }

    /**
     * Ensure, that the given model actually uses the RepresentsPlan trait.
     * If it doesn't, print out an error message and exit the command.
     *
     * @param \Illuminate\Database\Eloquent\Model $Plan
     */
    private function checkIfModelRepresentsPlan(Model $Plan): void
    {
        $traits = class_uses($Plan);

        if (!collect($traits)->contains(RepresentsPlan::class)) {
            $this->error('The given model is not a Plan.');
            exit(1);
        }
    }

    /**
     * Ensure, that the given model actually uses the RepresentsProvider trait.
     * If it doesn't, print out an error message and exit the command.
     *
     * @param \Illuminate\Database\Eloquent\Model $Plan
     */
    private function checkIfModelRepresentsProvider(Model $Plan): void
    {
        $traits = class_uses($Plan);

        if (!collect($traits)->contains(RepresentsProvider::class)) {
            $this->error('The given model is not a Provider.');
            exit(1);
        }
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model $Model
     *
     * @return string
     */
    private function getTableOfModel(Model $Model): string
    {
        return $Model->getTable();
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model $Plan
     *
     * @return void
     */
    private function makeMigrationForPlans(Model $Plan)
    {
        $table = $this->getTableOfModel($Plan);

        $stub_path = $this->getPlanStubPath();

        $stub = $this->getStub($stub_path);

        $this->replaceClassName($stub, $table, "Create{{TABLE}}Table");

        $this->replaceTableName($stub, $table);

        $this->saveMigration($stub, $table, "create_{{TABLE}}_table");
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model $Plan
     * @param \Illuminate\Database\Eloquent\Model $Provider
     *
     * @return void
     */
    private function makeMigrationForPlanProvider(Model $Plan, Model $Provider)
    {
        $plans_table = $this->getTableOfModel($Plan);
        $provider_table = $this->getTableOfModel($Provider);

        $plan_singular = $this->getSingular($plans_table);
        $provider_singular = $this->getSingular($provider_table);

        $names = [
            $plan_singular,
            $provider_singular,
        ];
        sort($names);

        $table_name = "{$names[0]}_{$names[1]}";
        $class_name = ucfirst($names[0]) . ucfirst($names[1]);

        $stub_path = $this->getPlanProviderStubPath();

        $stub = $this->getStub($stub_path);

        $this->replaceClassName($stub, $class_name, "Create{{TABLE}}Table");

        $this->replaceTableName($stub, $table_name);

        $stub = str_replace('{{NAME_1_SINGULAR}}', $names[0], $stub);
        $stub = str_replace('{{NAME_2_SINGULAR}}', $names[1], $stub);

        $names = [
            $plans_table,
            $provider_table,
        ];
        sort($names);

        $stub = str_replace('{{NAME_1_PLURAL}}', $names[0], $stub);
        $stub = str_replace('{{NAME_2_PLURAL}}', $names[1], $stub);

        $this->saveMigration($stub, $table_name, "create_{{TABLE}}_table", Carbon::now()
            ->addMinute());
    }

    /**
     * Return singular of a plural.
     *
     * @param string $plural
     *
     * @return string
     */
    private function getSingular(string $plural): string
    {
        return Str::singular($plural);
    }

    /**
     * @return string
     */
    private function getPlanStubPath(): string
    {
        $stub_path = base_path('vendor/marqant-lab/marqant-pay-subscriptions/stubs/create_plans_table.stub');

        return $stub_path;
    }

    /**
     * @return string
     */
    private function getPlanProviderStubPath(): string
    {
        $stub_path = base_path('vendor/marqant-lab/marqant-pay-subscriptions/stubs/create_plan_provider_table.stub');

        return $stub_path;
    }

    /**
     * Returns the blueprint for the migration about to be created.
     *
     * @param string $stub_path
     *
     * @return string
     */
    private function getStub(string $stub_path): string
    {
        return file_get_contents($stub_path);
    }

    /**
     * @param string $stub
     *
     * @param string $table
     *
     * @param        $class_name_template
     *
     * @return string
     */
    private function replaceClassName(string &$stub, string $table, $class_name_template): string
    {
        // table => Table
        $table = ucfirst($table);

        $class_name = str_replace('{{TABLE}}', $table, $class_name_template);

        $stub = str_replace('{{CLASS_NAME}}', $class_name, $stub);

        return $stub;
    }

    /**
     * @param string $stub
     *
     * @param string $table
     *
     * @return string
     */
    private function replaceTableName(string &$stub, string $table): string
    {
        $stub = str_replace('{{TABLE_NAME}}', $table, $stub);

        return $stub;
    }

    /**
     * @param string                          $stub
     * @param string                          $table
     * @param string                          $file_name_template
     * @param null|\Illuminate\Support\Carbon $Timestamp
     *
     * @return void
     */
    private function saveMigration(string $stub, string $table, string $file_name_template,
                                   ?Carbon $Timestamp = null): void
    {
        $file_name_template = str_replace('{{TABLE}}', $table, $file_name_template);

        $file_name = $this->getMigrationFileName($table, $file_name_template, $Timestamp);

        $path = database_path('migrations');

        $this->preventDuplicates($path, $table, $file_name_template);

        File::put($path . '/' . $file_name, $stub);
    }

    /**
     * @param null|\Illuminate\Support\Carbon $Timestamp
     *
     * @return string
     */
    private function getMigrationPrefix(?Carbon $Timestamp = null): string
    {
        $format = 'Y_m_d_His';

        if ($Timestamp) {
            return $Timestamp->format($format);
        }

        return Carbon::now()
            ->format($format);
    }

    /**
     * @param string                          $table
     * @param string                          $file_name_template
     *
     * @param \Illuminate\Support\Carbon|null $Timestamp
     *
     * @return string
     */
    private function getMigrationFileName(string $table, string $file_name_template, ?Carbon $Timestamp = null): string
    {
        $prefix = $this->getMigrationPrefix($Timestamp);

        $file_name = str_replace('{{TABLE}}', $table, $file_name_template);

        return "{$prefix}_{$file_name}.php";
    }

    /**
     * @param string $path
     * @param string $table
     * @param string $file_name_template
     */
    private function preventDuplicates(string $path, string $table, string $file_name_template)
    {
        $file = str_replace('{{TABLE}}', $table, $file_name_template . '.php');

        $files = collect(File::files($path))
            ->map(function (SplFileInfo $file) {
                return $file->getFilename();
            })
            ->map(function (string $file_name) {
                return preg_replace('/[0-9]{4}_[0-9]{2}_[0-9]{2}_[0-9]{6}_/', '', $file_name);
            });

        if ($files->contains($file)) {
            $this->error("Migration for marqant pay stripe fields on {$table} already exists.");
            exit(1);
        }
    }
}

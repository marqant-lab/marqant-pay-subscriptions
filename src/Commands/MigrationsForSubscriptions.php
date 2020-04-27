<?php

namespace Marqant\MarqantPaySubscriptions\Commands;

use File;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\Finder\SplFileInfo;
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
        $Plan = $this->getPlansModel();

        $this->makeMigrationForPlans($Plan);

        $this->info('Done! 👍');
    }

    /**
     * Get billable argument from input and resolve it to a model with the Billable trait attached.
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
     * Ensure, that the given model actually uses the Billable trait.
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
     * @return string
     */
    private function getPlanStubPath(): string
    {
        $stub_path = base_path('vendor/marqant/marqant-pay-subscriptions/stubs/create_plans_table.stub');

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
     * @param string $stub
     * @param string $table
     * @param string $file_name_template
     *
     * @return void
     */
    private function saveMigration(string $stub, string $table, string $file_name_template): void
    {
        $file_name_template = str_replace('{{TABLE}}', $table, $file_name_template);

        $file_name = $this->getMigrationFileName($table, $file_name_template);

        $path = database_path('migrations');

        $this->preventDuplicates($path, $table, $file_name_template);

        File::put($path . '/' . $file_name, $stub);
    }

    /**
     * @return string
     */
    private function getMigrationPrefix(): string
    {
        return date('Y_m_d_His');
    }

    /**
     * @param string $table
     * @param string $file_name_template
     *
     * @return string
     */
    private function getMigrationFileName(string $table, string $file_name_template): string
    {
        $prefix = $this->getMigrationPrefix();

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
        $file = str_replace('{{TABLE}}', $table, $file_name_template);

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

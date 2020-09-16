<?php declare(strict_types=1);

namespace Sassnowski\LaravelWorkflow;

use Illuminate\Support\ServiceProvider;

class WorkflowServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/workflow.php' => config_path('workflow.php'),
        ]);
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/workflow.php',
            'workflow'
        );

        $this->app->bind(WorkflowRepository::class, function ($app) {
            return new DatabaseWorkflowRepository(
                $app->make('db')->connection(config('database.default')),
                config('workflow.workflow_table')
            );
        });
    }
}

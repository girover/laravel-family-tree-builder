<?php

namespace Girover\Tree;

use Girover\Tree\Commands\TreeCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class TreeServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('tree')
            ->hasConfigFile('tree')
            // ->hasViews()
            ->hasAssets()
            ->hasTranslations()
            ->hasMigrations(
                'create_node_images_table',
                'create_marriages_table',
                'create_nodes_table',
                'create_trees_table'
            );
    }

    public function boot()
    {
        
        if ($this->app->runningInConsole()) {
            $this->commands([
                TreeCommand::class,
            ]);

            // to publish photos folder to storage folder
            $this->publishes([
                $this->package->basePath('/../resources/storage') => base_path("storage/app/public"),
            ], "{$this->package->shortName()}-storage");

        }

        parent::boot();
    }
}

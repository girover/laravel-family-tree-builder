<?php

namespace Girover\Tree;

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
            ->hasMigrations(
                'create_node_images_table',
                'create_marriages_table',
                'create_nodes_table',
                'create_trees_table'
            );
        // ->hasCommand(SkeletonCommand::class);

        $this->app->bind('FamilyTree', function ($app) {
            return new FamilyTree();
        });
    }
}

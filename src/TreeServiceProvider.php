<?php

namespace Girover\Tree;

use Girover\Tree\Commands\TreeCommand;
use Illuminate\Support\Facades\Blade;
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
                // 'create_node_images_table',
                // 'create_treeables_table',
                // 'create_nodeables_table',
                'create_nodes_table',
                'create_marriages_table'
            );
    }

    public function boot()
    {
        
        if ($this->app->runningInConsole()) {
            $this->commands([
                TreeCommand::class,
            ]);

            // to publish photos folder to storage folder
            // $this->publishes([
            //     $this->package->basePath('/../resources/storage') => base_path("storage/app/public"),
            // ], "{$this->package->shortName()}-storage");

            // to publish photos folder to storage folder
            // $this->publishes([
            //     $this->package->basePath('/../resources/storage') => public_path(),
            // ], "{$this->package->shortName()}-avatars");

            // to publish photos folder to public folder
            // $this->publishes([
            //     $this->package->basePath('/../resources/storage') => public_path(),
            // ], "{$this->package->shortName()}-avatars");

            // to publish model Girover\Tree\Models\Node
            // $this->publishes([
            //     $this->package->basePath('Models/Nodeable.php') => app_path().'/Models/Nodeable.php',
            //     $this->package->basePath('Models/Treeable.php') => app_path().'/Models/Treeable.php',
            // ], "{$this->package->shortName()}-models");

        }
        // @tree($html) Blade directive to render the tree 
        Blade::directive('tree', function ($tree_html) {
            return "<?php echo $tree_html; ?>";
        });

        parent::boot();
    }
}

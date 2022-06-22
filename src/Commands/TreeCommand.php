<?php

namespace Girover\Tree\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Girover\Tree\TreeServiceProvider;

class TreeCommand extends Command
{
    public $signature = 'tree:install';

    public $description = 'Installing girover\\tree package';

    public function handle()
    {
        $this->info('Starting installing package girover\\tree');
        $this->publishConfigFile();
        $this->publishMigrations();
        $this->runMigrate();
        $this->publishAssets();
        $this->publishAvatars();
        // $this->publishModels();
        // $this->publishStorage();
        $this->publishTranslations();
        // $this->symbolicLink();
    }

    public function treePublish($tag = '')
    {
        if ($tag === '') {
            return 'please provide --tag to run the command';
        }

        $r = Artisan::call('vendor:publish', [
            '--provider'=> TreeServiceProvider::class,
            '--tag' => $tag
        ]);
        if ($r === 0) {
            return $this->line('<fg=green>Succeed</>');
        }
        return $this->line('<fg=red>Failed</>');
    }
    public function publishConfigFile()
    {
        $this->line('<fg=yellow>Publishing config file Tree.php....</>');
        return $this->treePublish('tree-config');
    }

    public function publishMigrations()
    {
        $this->info('<fg=yellow>Publishing Migrations....</>');
        return $this->treePublish('tree-migrations');
    }

    public function runMigrate()
    {
        $this->info('<fg=yellow>Migrating database....</>');
        if(Artisan::call('migrate') === 0){
            return $this->line('<fg=green>Succeed</>');
        }
        return $this->line('<fg=green>Faild</>');
    }

    public function publishAssets()
    {
        $this->info('<fg=yellow>Publishing Assets....</>');
        return $this->treePublish('tree-assets');
    }

    public function publishAvatars()
    {
        $this->info('<fg=yellow>Publishing Photos Folder to public folder....</>');
        return $this->treePublish('tree-avatars');
    }
    // public function publishStorage()
    // {
    //     $this->info('<fg=yellow>Publishing Photos Folder to Storage folder....</>');
    //     return $this->treePublish('tree-storage');
    // }

    public function publishTranslations()
    {
        $this->info('<fg=yellow>Publishing Translations....</>');
        return $this->treePublish('tree-translations');
    }

    // public function publishModels()
    // {
    //     $this->info('<fg=yellow>Publishing Models Nodeable && Treeable ....</>');
    //     return $this->treePublish('tree-models');
    // }

    public function symbolicLink()
    {
        $this->info('<fg=yellow>symbolic link to storage/app/public/vendor/tree/images</>');
        if(Artisan::call('storage:link') === 0){
            return $this->line('<fg=green>Succeed</>');
        }
        return $this->line('<fg=green>Failed</>');
    }

}

<?php

namespace Girover\Tree\Commands;

use Illuminate\Console\Command;

class TreeCommand extends Command
{
    public $signature = 'tree';

    public $description = 'My command';

    public function handle()
    {
        $this->comment('All done');
    }
}

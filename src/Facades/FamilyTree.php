<?php

namespace Girover\Tree\Facades;

use Illuminate\Support\Facades\Facade;

class FamilyTree extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'FamilyTree';
    }
}

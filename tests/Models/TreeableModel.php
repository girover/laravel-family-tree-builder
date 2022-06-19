<?php

namespace Girover\Tree\Tests\Models;

use Girover\Tree\Traits\Treeable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TreeableModel extends Model
{
    use Treeable, HasFactory;

    protected $table = 'treeables';
}
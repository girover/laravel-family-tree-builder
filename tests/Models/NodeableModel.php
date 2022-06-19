<?php

namespace Girover\Tree\Tests\Models;

use Girover\Tree\Traits\Nodeable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NodeableModel extends Model
{
    use Nodeable, HasFactory;

    protected $table = 'nodeables';
}
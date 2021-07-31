<?php

namespace Girover\Tree\Models;

use Girover\Tree\Traits\Nodeable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Node extends Model
{
    
    use Nodeable;
    
}

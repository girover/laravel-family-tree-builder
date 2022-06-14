<?php

namespace Girover\Tree\Models;

use Girover\Tree\Traits\Nodeable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Node extends Model
{
    use Nodeable, HasFactory;

}

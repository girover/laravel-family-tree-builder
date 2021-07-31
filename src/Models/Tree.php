<?php

namespace Girover\Tree\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tree extends Model
{
    // use SoftDeletes;

    protected $fillable = ['user_id','name', 'basic_node'];
    
}

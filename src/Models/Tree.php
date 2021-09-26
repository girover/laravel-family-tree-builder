<?php

namespace Girover\Tree\Models;

use Girover\Tree\Traits\Treeable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tree extends Model
{
    // use SoftDeletes;
    use Treeable, HasFactory;

    // protected $fillable = ['user_id','name', 'basic_node'];
    protected $guarded = [];

}

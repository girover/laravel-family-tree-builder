<?php

namespace App\Models;

use Girover\Tree\Traits\Treeable as TreeableTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Treeable extends Model
{
    // use SoftDeletes;
    use TreeableTrait, HasFactory;

    // protected $fillable = ['user_id','name', 'basic_node'];
    // protected $fillable = ['treeable_id'];
    protected $guarded = [];


}

<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = ['descr', 'code', 'version', 'isMark', 'parent_id', 'parent_code'];
	
	public function products(){
	    return $this->hasMany("App\Product");
    }
}

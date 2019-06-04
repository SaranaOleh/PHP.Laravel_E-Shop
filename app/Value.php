<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Value extends Model
{
    public $timestamps = false;


    public function products()
    {
        return $this->belongsToMany(Product::class,'Product_has_Attribute');
    }
}

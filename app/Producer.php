<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Producer extends Model
{
    public $timestamps = false;

    public function images()
    {
        return $this->belongsToMany(Image::class,'Producer_has_Image');
    }

    public function product()
    {
        return $this->hasMany(Product::class,'Producer_id');
    }
}

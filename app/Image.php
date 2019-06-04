<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Image extends Model
{
    public $timestamps = false;

    public function producer()
    {
        return $this->belongsToMany(Producer::class,'Producer_has_Image');
    }

    public function product()
    {
        return $this->belongsToMany(Producer::class,'Product_has_Image');
    }
}

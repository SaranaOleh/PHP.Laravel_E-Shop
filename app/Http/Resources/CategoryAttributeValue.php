<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CategoryAttributeValue extends JsonResource
{
    public function toArray($request)
    {
//        $arr = [];
//        foreach ($request as $item){
//            var_dump($item);
//        }



        return $request;

//        return [
//            "vasia" => "pupkin",
//            "petia" => "pyatocjkin"
//        ];
//        return parent::toArray($request);
    }
}

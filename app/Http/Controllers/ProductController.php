<?php

namespace App\Http\Controllers;

use App\Attribute;
use App\Category;
use App\Description;
use App\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    public function __construct()
    {
        $this->middleware(['ApiAuth', 'ApiIsAdmin'])->except(['index', 'show', 'indexFilters', 'indexSearch']);
    }

    public function index()
    {
        if (count($_GET) > 0) {
            $order = $_GET['order'];
            $direction = $_GET['direction'];
            $paginate = 12;

        } else {
            $order = 'id';
            $direction = 'ASC';
            $paginate = 1000;
        }

        try {
            $prod = Product::with(['category', 'producer','attributes','values', 'reviews'])
                ->orderBy($order, $direction)
                ->paginate($paginate);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
        return response()->json(compact('prod'));
    }

    public function indexSearch($value)
    {
        $paginate = 5;
        if(isset($_GET['pag'])) $paginate = $_GET['pag'];
        $tmpStr = '%'.str_replace(' ','%',$value).'%';

        try {
            $prod = Product::with(['category', 'producer','attributes','values','reviews'])
                ->where('name', 'like', $tmpStr)
                ->orderBy('name', 'ASC')
                ->paginate($paginate);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
        return response()->json(compact('prod'));

    }

    public function indexFilters(Request $request, $category)
    {
        $filters = false;
        if (count($_GET) > 0) {
            $order = $_GET['order'];
            $direction = $_GET['direction'];
            $paginate = 9;
            if(isset($_GET['priceMin']) && isset($_GET['priceMax'])  ){
                $priceMin = (int)$_GET['priceMin'];
                if((int)$_GET['priceMax']){
                    $priceMax = (int)$_GET['priceMax'];
                }
                else{
                    $priceMax = 10000000;
                }
            }
            else{
                $priceMin = 0;
                $priceMax = 10000000;
            }


            if(count($_GET) > 5){
                $filters = true;
                $attrs = [];
                $values = [];
                foreach ($_GET as $elem => $value){
                    if($elem !== 'order' && $elem !== 'direction' && $elem !== 'page' &&
                        $elem !== 'priceMax' && $elem !== 'priceMin'){
                        array_push($attrs,$elem);
                        $values = array_merge($values,explode(',',trim($_GET[$elem],',')));
                    }
                }
                $count = count($attrs);
            }
        } else {
            $order = 'name';
            $direction = 'ASC';
            $paginate = 1000;
            $attrs = ['*'];
            $values = ['*'];
            $priceMax = 10000000;
            $priceMin = 1;
        }

        try{
            $cat = Category::where('name',$category)->pluck('id')->first();

            if($filters){
                $prod = DB::table('products as P')
                    ->join('Product_has_Attribute as PA','PA.Product_id','=','P.id')
                    ->join('attributes as A','A.id','=','PA.Attribute_id')
                    ->join('values as V','V.id','=','PA.Value_id')
                    ->where('P.Category_id',$cat)
                    ->whereIn('V.value',$values)
                    ->whereIn('A.name',$attrs)
                    ->whereBetween('P.price', [$priceMin, $priceMax])
                    ->select('P.*')
                    ->groupBy('P.name')
                    ->havingRaw('COUNT(*) = ?', [$count])
                    ->orderByRaw('amount > 0 DESC, '.$order.' '.$direction)
                    ->paginate($paginate);

                $tmpProd = DB::table('products as P')
                    ->join('Product_has_Attribute as PA','PA.Product_id','=','P.id')
                    ->join('attributes as A','A.id','=','PA.Attribute_id')
                    ->join('values as V','V.id','=','PA.Value_id')
                    ->where('P.Category_id',$cat)
                    ->whereIn('V.value',$values)
                    ->whereIn('A.name',$attrs)
                    ->whereBetween('P.price', [$priceMin, $priceMax])
                    ->select('P.name','P.price')
                    ->groupBy('P.name')
                    ->havingRaw('COUNT(*) = ?', [$count])
                    ->get();
                if(count($tmpProd) > 0){
                    $rangePrice = [];
                    $tmpPrice = [];
                    foreach ($tmpProd as $key => $value){
                        array_push($tmpPrice,(int)$value->price);
                    }
                    $rangePrice['minPrice'] = min($tmpPrice);
                    $rangePrice['maxPrice'] = max($tmpPrice);
                }

            }
            else{
                $prod = DB::table('products as P')
                    ->where('P.Category_id',$cat)
                    ->whereBetween('P.price', [$priceMin, $priceMax])
                    ->select('P.*')
                    ->orderByRaw('amount > 0 DESC, '.$order.' '.$direction)
                    ->paginate($paginate);

                $tmpProd = DB::table('products as P')
                    ->where('P.Category_id',$cat)
                    ->whereBetween('P.price', [$priceMin, $priceMax])
                    ->select('P.price')
                    ->get();

                if(count($tmpProd) > 0){
                    $rangePrice = [];
                    $tmpPrice = [];
                    foreach ($tmpProd as $key => $value){
                        array_push($tmpPrice,(int)$value->price);
                    }
                    $rangePrice['minPrice'] = min($tmpPrice);
                    $rangePrice['maxPrice'] = max($tmpPrice);
                }

            }
        }catch (\Exception $e){
            return response()->json(['error' => $e->getMessage()]);
        }
        return response()->json(compact(['prod','rangePrice','test']));
    }


    public function store(Request $request)
    {
        $attrs = json_decode($request->attrs);
        $image_gallery = [];
        $files = $request->allFiles();

        foreach ($files as $key => $value){
            if($key !== 'image_basic') array_push($image_gallery,$value);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|min:2|max:120|unique:products',
            'producer' => 'required|numeric|exists:producers,id',
            'category' => 'required|numeric|exists:categories,id',
            'amount' => 'required|numeric',
            'price' => 'required|numeric|max:1000000',
            'price_old' => 'numeric|max:10000000',
            'description' => 'required|string',
            'attrs' => 'required|string',
            'image_basic' => 'required|image'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors());
        }

        foreach ($image_gallery as $value){
            $image = array('image' => $value);
            $validatorGallery = Validator::make($image,['image' => 'image']);

            if($validatorGallery->fails()){
                return response()->json(['error' => 'В галерее могут быть только изображения!']);
            }
        }
        $arrayGallery = [];
        $arrayIds = [];
        $basic_image = "";
        $storedImages = [];
        try{

            $attr = DB::table('products as P')
                ->join('Product_has_Attribute as PA','PA.Product_id','=','P.id')
                ->join('attributes as A','A.id','=','PA.Attribute_id')
                ->join('values as V','V.id','=','PA.Value_id')
                ->where('P.Category_id','=',(int)$request->category)
                ->select('A.name')
                ->groupBy('V.value')
                ->get();
//            $fixAttr = Category::with('attributes')->where('id',(int)$request->category)->first();
//            $attr = $fixAttr->attributes;
//            return response()->json($attr);

            $oldAttrs = [];
            foreach ($attr as $value){
                if(!in_array($value->name,$oldAttrs)){
                    array_push($oldAttrs,$value->name);
                }
            }

            $newAttrs =[];
            foreach ($attrs as $value){
                if(!in_array($value->name,$oldAttrs)){
                    array_push($newAttrs,$value->name);
                }
            }

            $arrayAttrIndexes = [];

            $addedOldAttrs = Attribute::whereIn('name',$newAttrs)->get();
            foreach ($addedOldAttrs as $value){
                array_push($arrayAttrIndexes,$value->id);
            }


            $temporaryAttrs = [];
            foreach ($addedOldAttrs as $value){
                array_push($temporaryAttrs,strtolower($value->name));
            }
            $addedNewAttrs = array_diff($newAttrs,$temporaryAttrs);


//        формирование новых значений

            $newRequestValues =[];
            foreach ($attrs as $value){
                if($value->Value_id === 'new') array_push($newRequestValues, $value->value);
            }

            $oldAddedValues = DB::table('values')
                ->whereIn('value',$newRequestValues)
                ->get();

            $oldAddedValuesAttr = [];
            foreach ($oldAddedValues as $value){
                array_push($oldAddedValuesAttr,$value->value);
            }

            $newAddedValues = [];
            foreach ($newRequestValues as $value){
                if(!in_array($value,$oldAddedValuesAttr)) array_push($newAddedValues,$value);
            }

            $arrayNewValues = [];
            $newAddedValues = array_unique($newAddedValues);
            foreach ($newAddedValues as $value){
                array_push($arrayNewValues,['value' => $value]);
            }

            DB::beginTransaction();

            DB::table('values')->insert($arrayNewValues);

            $newValues = DB::table('values')
                ->whereIn('value',$newAddedValues)
                ->get();

            $newValuesArray = [];
            foreach ($oldAddedValues as $value){
                array_push($newValuesArray,$value);
            }
            foreach ($newValues as $value){
                array_push($newValuesArray,$value);
            }

            foreach ($files as $key => $value){
                $exc = $value->extension();
                $name = str_random(40).'.'.$exc;
                $value->move(public_path('images/'), $name);
                $url = url('images/'.$name);
                array_push($storedImages,$url);

                if($key === 'image_basic'){
                    $basic_image = $url;
                }else{
                    array_push($arrayIds,$url);
                    array_push($arrayGallery,['url' => $url]);
                }
            }


            $description = new Description;
            $description->text = $request['description'];
            $description->save();

            $product = new Product;
            $product->name = $request['name'];
            $product->amount = $request['amount'];
            $product->price = $request['price'];
            $product->price_old = $request['price_old'];
            $product->Category_id = $request['category'];
            $product->Producer_id = $request['producer'];
            $product->Description_id = $description->id;
            $product->image = $basic_image;
            $product->save();


            if(count($addedNewAttrs) > 0){
                $arrayNewAttr = [];

                foreach ($addedNewAttrs as $value){
                    array_push($arrayNewAttr,['name' => $value, 'Group_id' => 1]);
                }

                DB::table('attributes')->insert($arrayNewAttr);

                $idsAttr = DB::table('attributes')
                    ->whereIn('name',$addedNewAttrs)
                    ->select('attributes.id','attributes.name')
                    ->get();

                foreach ($idsAttr as $key){
                    array_push($arrayAttrIndexes,$key->id);
                }
            }

//            подставление id новых атрибут-значений

            $attachedProductAttrs = [];
            foreach ($attrs as $value){
//                новые атрибуты
                if( $value->Attribute_id === 'new'){
                    foreach ($idsAttr as $newValue){
                        if($newValue->name === $value->name){
                            foreach ($newValuesArray as $newAddedValue){
                                if( $newAddedValue->value === $value->value){
                                    array_push($attachedProductAttrs,(array)[
                                        'Attribute_id' => $newValue->id,
                                        'Value_id' => $newAddedValue->id,
                                        'name' => $value->name,
                                        'value' => $value->value
                                    ]);
                                }
                            }
                        }
                    }

                }
                else{

//                    новые значения

                    if($value->Value_id !== 'new'){
                        array_push($attachedProductAttrs, (array)$value);
                    }
                    else{
                        foreach ($newValuesArray as $newAddedValue){
                            if($newAddedValue->value === $value->value){
                                array_push($attachedProductAttrs,(array)[
                                    'Attribute_id' => $value->Attribute_id,
                                    'Value_id' => $newAddedValue->id,
                                    'name' => $value->name,
                                    'value' => $value->value
                                ]);
                            }
                        }
                    }

                }
            }

            $attachedAttrs = [];
            foreach ($attachedProductAttrs as $tempAttr){
                $attachedAttrs[(int)$tempAttr['Attribute_id']] = ['Value_id' => (int)$tempAttr['Value_id']];
            }

//            return response()->json($attachedAttrs);

            $product->attributes()->attach($attachedAttrs);

//            здесь добавление значений

            DB::table('images')->insert($arrayGallery);

            $ids = DB::table('images')
                ->whereIn('url', $arrayIds)
                ->select('images.id')
                ->get();

            $newId = [];

            foreach ($ids as $key){
                array_push($newId,$key->id);
            }

            $product->images()->attach($newId);

            DB::commit();
        }catch (\Exception $e){
            DB::rollBack();

            foreach ($storedImages as $image){
                $url = explode("/", $image);
                $path = public_path('images').'\\'.end($url);
                unlink($path);
            }

            return response()->json(['error' => $e->getMessage()]);
        }

        return response()->json(['status' => 'success']);
    }

    public function show($id)
    {
        try{
            $prod = Product::with(['images','description','attributes','values','reviews','category'])
                ->where('id','=',$id)->first();
        }catch (\Exception $e){
            return response()->json(['error' => $e->getMessage()]);
        }

        return response()->json($prod);
    }

    public function update(Request $request)
    {

        $oldIndexes = empty($request->oldImage) ? [] : array_unique(json_decode($request->oldImage));
        $oldBasic = null;
        $attrs = json_decode($request->attrs);

        $image_gallery = [];
        $files = $request->allFiles();

        $product = Product::find($request->id);

        $detachedOldAttrs = $product->values()->get()->pluck('id')->toArray();

        if($product->name !== $request['name']){

            $validator = Validator::make($request->all(), [
                'name' => 'required|min:2|max:120|unique:producers',
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors());
            }
        }

        if($request->image_basic !== 'null'){
            $oldBasic = $product->image;
            $validator = Validator::make($request->all(), [
                'image_basic' => 'image'
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors());
            }
        }

        foreach ($files as $key => $value){
            if($key !== 'image_basic') array_push($image_gallery,$value);
        }

        $validator = Validator::make($request->all(), [
            'producer' => 'required|numeric|exists:producers,id',
            'category' => 'required|numeric|exists:categories,id',
            'amount' => 'required|numeric',
            'price' => 'required|numeric|min:2|max:1000000',
            'price_old' => 'min:2|max:10000000',
            'description' => 'string',
            'attrs' => 'string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors());
        }

        foreach ($image_gallery as $value){
            $image = array('image' => $value);
            $validatorGallery = Validator::make($image,['image' => 'image']);

            if($validatorGallery->fails()){
                return response()->json(['error' => 'В галерее могут быть только изображения!']);
            }
        }
        $arrayGallery = [];
        $arrayIds = [];
        $basic_image = "";
        $storedImages = [];
        try{

            $attr = DB::table('products as P')
                ->join('Product_has_Attribute as PA','PA.Product_id','=','P.id')
                ->join('attributes as A','A.id','=','PA.Attribute_id')
                ->join('values as V','V.id','=','PA.Value_id')
                ->where('P.Category_id','=',(int)$request->category)
                ->select('A.name')
                ->groupBy('V.value')
                ->get();

            $oldAttrs = [];
            foreach ($attr as $value){
                if(!in_array($value->name,$oldAttrs)){
                    array_push($oldAttrs,$value->name);
                }
            }

            $newAttrs =[];
            foreach ($attrs as $value){
                if(!in_array($value->name,$oldAttrs)){
                    array_push($newAttrs,$value->name);
                }
            }

            $arrayAttrIndexes = [];

            $addedOldAttrs = Attribute::whereIn('name',$newAttrs)->get();
            foreach ($addedOldAttrs as $value){
                array_push($arrayAttrIndexes,$value->id);
            }


            $temporaryAttrs = [];
            foreach ($addedOldAttrs as $value){
                array_push($temporaryAttrs,strtolower($value->name));
            }
            $addedNewAttrs = array_diff($newAttrs,$temporaryAttrs);

//        формирование новых значений

            $newRequestValues =[];
            foreach ($attrs as $value){
                if($value->Value_id === 'new') array_push($newRequestValues, $value->value);
            }

            $oldAddedValues = DB::table('values')
                ->whereIn('value',$newRequestValues)
                ->get();

            $oldAddedValuesAttr = [];
            foreach ($oldAddedValues as $value){
                array_push($oldAddedValuesAttr,$value->value);
            }

            $newAddedValues = [];
            foreach ($newRequestValues as $value){
                if(!in_array($value,$oldAddedValuesAttr)) array_push($newAddedValues,$value);
            }

            $arrayNewValues = [];
            $newAddedValues = array_unique($newAddedValues);
            foreach ($newAddedValues as $value){
                array_push($arrayNewValues,['value' => $value]);
            }



            DB::table('values')->insert($arrayNewValues);

            $newValues = DB::table('values')
                ->whereIn('value',$newAddedValues)
                ->get();

            $newValuesArray = [];
            foreach ($oldAddedValues as $value){
                array_push($newValuesArray,$value);
            }
            foreach ($newValues as $value){
                array_push($newValuesArray,$value);
            }

            foreach ($files as $key => $value){
                $exc = $value->extension();
                $name = str_random(40).'.'.$exc;
                $value->move(public_path('images/'), $name);
                $url = url('images/'.$name);
                array_push($storedImages,$url);

                if($key === 'image_basic'){
                    $basic_image = $url;
                }else{
                    array_push($arrayIds,$url);
                    array_push($arrayGallery,['url' => $url]);
                }
            }
            DB::beginTransaction();

            $description = Description::find($request->Description_id);
            $description->text = $request['description'];
            $description->save();


            $product->name = $request['name'];
            $product->amount = $request['amount'];
            $product->price = $request['price'];
            $product->price_old = $request['price_old'];
            $product->Category_id = $request['category'];
            $product->Producer_id = $request['producer'];
            $product->Description_id = $description->id;
            if(!empty($basic_image)) $product->image = $basic_image;
            $product->save();

            if(count($addedNewAttrs) > 0){
                $arrayNewAttr = [];

                foreach ($addedNewAttrs as $value){
                    array_push($arrayNewAttr,['name' => $value, 'Group_id' => 1]);
                }

                DB::table('attributes')->insert($arrayNewAttr);

                $idsAttr = DB::table('attributes')
                    ->whereIn('name',$addedNewAttrs)
                    ->select('attributes.id','attributes.name')
                    ->get();

                foreach ($idsAttr as $key){
                    array_push($arrayAttrIndexes,$key->id);
                }
            }

//            подставление id новых атрибут-значений

            $attachedProductAttrs = [];
            foreach ($attrs as $value){
//                новые атрибуты
                if( $value->Attribute_id === 'new'){
                    foreach ($idsAttr as $newValue){
                        if($newValue->name === $value->name){
                            foreach ($newValuesArray as $newAddedValue){
                                if( $newAddedValue->value === $value->value){
                                    array_push($attachedProductAttrs,(array)[
                                        'Attribute_id' => $newValue->id,
                                        'Value_id' => $newAddedValue->id,
                                        'name' => $value->name,
                                        'value' => $value->value
                                    ]);
                                }
                            }
                        }
                    }

                }
                else{

//                    новые значения

                    if($value->Value_id !== 'new'){
                        array_push($attachedProductAttrs, (array)$value);
                    }
                    else{
                        foreach ($newValuesArray as $newAddedValue){
                            if($newAddedValue->value === $value->value){
                                array_push($attachedProductAttrs,(array)[
                                    'Attribute_id' => $value->Attribute_id,
                                    'Value_id' => $newAddedValue->id,
                                    'name' => $value->name,
                                    'value' => $value->value
                                ]);
                            }
                        }
                    }

                }
            }

            $product->values()->detach($detachedOldAttrs);

            $attachedAttrs = [];
            foreach ($attachedProductAttrs as $tempAttr){
                $attachedAttrs[(int)$tempAttr['Attribute_id']] = ['Value_id' => (int)$tempAttr['Value_id']];
            }

            $product->attributes()->attach($attachedAttrs);

//            здесь добавление значений

            DB::table('images')->insert($arrayGallery);

            $ids = DB::table('images')
                ->whereIn('url', $arrayIds)
                ->select('images.id')
                ->get();

            $newId = [];

            foreach ($ids as $key){
                array_push($newId,$key->id);
            }

            $product->images()->attach($newId);

            $oldImages = DB::table('images')
                ->whereIn('id', $oldIndexes)
                ->select('images.url')
                ->get();

            DB::table('images')
                ->whereIn('id', $oldIndexes)
                ->delete();

            $oldImage = [];
            if(!empty($oldBasic)) array_push($oldImage,$oldBasic);

            if(count($oldImages) > 0){
                foreach ($oldImages as $key){
                    array_push($oldImage,$key->url);
                }
            }

            if(count($oldImage) > 0){
                foreach ($oldImage as $image){
                    $url = explode("/", $image);
                    $path = public_path('images').'\\'.end($url);
                    unlink($path);
                }
            }

            DB::commit();
        }catch (\Exception $e){
            DB::rollBack();

            foreach ($storedImages as $image){
                $url = explode("/", $image);
                $path = public_path('images').'\\'.end($url);
                unlink($path);
            }

            return response()->json(['error' => $e->getMessage()]);
        }

        return response()->json(['status' => 'success']);
    }

    public function destroy(Request $request)
    {
        try{
            $product = Product::find($request->id);
            $attrs = $product->attributes()->get()->pluck('id')->toArray();
            $images = $product->images()->get()->pluck('url')->toArray();
            array_push($images,$product->image);

            DB::beginTransaction();

            DB::table('images')
                ->whereIn('url', $images)
                ->delete();

            Product::destroy($product->id);

            DB::table('descriptions')->where('id','=',$product->Description_id)->delete();

            foreach ($images as $image){
                $url = explode("/", $image);
                $path = public_path('images').'\\'.end($url);
                unlink($path);
            }
            $product->attributes()->detach($attrs);
            DB::commit();
        }catch (\Exception $e){
            DB::rollBack();

            return response()->json(['error' => $e->getMessage()]);
        }

        return response()->json(['status' => 'success']);
    }
}

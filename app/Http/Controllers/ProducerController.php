<?php

namespace App\Http\Controllers;

use App\Image;
use App\Producer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProducerController extends Controller
{
    public function __construct()
    {
        $this->middleware(['ApiAuth', 'ApiIsAdmin'])->except(['index','show']);
    }

    public function index()
    {
        if(count($_GET) > 0){
            $order = $_GET['order'];
            $direction = $_GET['direction'];
            $paginate = 12;

        }
        else{
            $order = 'id';
            $direction = 'ASC';
            $paginate = 1000;
        }

        try{
            $producer = Producer::orderBy($order, $direction)->paginate($paginate);
        }catch (\Exception $e){
            return response()->json(['error' => $e->getMessage()]);
        }

        return response()->json(compact('producer'));
    }

    public function store(Request $request)
    {
        $image_gallery = [];
        $files = $request->allFiles();

        foreach ($files as $key => $value){
            if($key !== 'image_basic') array_push($image_gallery,$value);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|alpha_dash|min:2|max:120|unique:producers',
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

            $producer = new Producer;
            $producer->name = $request['name'];
            $producer->image = $basic_image;
            $producer->save();

            DB::table('images')->insert($arrayGallery);

            $ids = DB::table('images')
                ->whereIn('url', $arrayIds)
                ->select('images.id')
                ->get();

            $newId = [];

            foreach ($ids as $key){
                array_push($newId,$key->id);
            }

            $producer->images()->attach($newId);

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
        $producer = Producer::with('images')->where('id','=',$id)->first();

        return response()->json($producer);
    }

    public function update(Request $request)
    {
        $oldIndexes = empty($request->oldImage) ? [] : array_unique(json_decode($request->oldImage));
        $oldBasic = null;
        $image_gallery = [];
        $files = $request->allFiles();

        foreach ($files as $key => $value){
            if($key !== 'image_basic') array_push($image_gallery,$value);
        }

        $producer = Producer::find($request['id']);

        if($producer->name !== $request['name']){

            $validator = Validator::make($request->all(), [
                'name' => 'required|alpha_dash|min:2|max:120|unique:producers',
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors());
            }
        }

        if($request->image_basic !== 'null'){
            $oldBasic = $producer->image;
            $validator = Validator::make($request->all(), [
                'image_basic' => 'image'
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors());
            }
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

            $producer->name = $request['name'];

            if(!empty($basic_image)) $producer->image = $basic_image;

            $producer->save();

            DB::table('images')->insert($arrayGallery);

            $ids = DB::table('images')
                ->whereIn('url', $arrayIds)
                ->select('images.id')
                ->get();

            $newId = [];

            foreach ($ids as $key){
                array_push($newId,$key->id);
            }

            $producer->images()->attach($newId);

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
            $producer = Producer::find($request->id);
            $images = $producer->images()->get()->pluck('url')->toArray();
            array_push($images,$producer->image);

            DB::beginTransaction();

            DB::table('images')
                ->whereIn('url', $images)
                ->delete();

            Producer::destroy($producer->id);

            foreach ($images as $image){
                $url = explode("/", $image);
                $path = public_path('images').'\\'.end($url);
                unlink($path);
            }

            DB::commit();
        }catch (\Exception $e){
            DB::rollBack();
            if(str_contains($e->getMessage(),'Cannot delete or update a parent row')){
                return response()->json(['error' => 'У производителя есть продукты!']);
            }
            return response()->json(['error' => $e->getMessage()]);
        }

        return response()->json(['status' => 'success']);
    }
}

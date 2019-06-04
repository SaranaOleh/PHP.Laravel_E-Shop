<?php

namespace App\Http\Controllers;

use App\Category;
use App\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware(['ApiAuth', 'ApiIsAdmin'])->except(['index','show','indexFilters']);
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
            $category = Category::with(['attributes','parent','children'])
                ->orderBy($order, $direction)
                ->paginate($paginate);
        }catch (\Exception $e){
            return response()->json(['error' => $e->getMessage()]);
        }

        return response()->json(compact('category'));
    }

    public function store(Request $request)
    {

        $pc = Category::with('product')
            ->where('id',$request->parent_category)
            ->first();

        if(!empty($pc)){
            if(count($pc->product) > 0){
                return response()->json(['error' => "В родительской категории есть товары, выберите другую"]);
            }
        }


        $validator = Validator::make($request->all(), [
            'name' => 'required|min:2|max:120|unique:categories',
            'image' => 'required|image'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors());
        }


        $stored_image = "";
        try{
            $exc = $request->image->extension();
            $name = str_random(40).'.'.$exc;
            $request->file('image')->move(public_path('images/'), $name);
            $url = url('images/'.$name);
            $stored_image = $url;

            DB::beginTransaction();

            $category = new Category;
            $category->name = $request['name'];
            $category->parent_category = (int)$request['parent_category'];
            $category->image = $url;
            $category->save();

            DB::commit();
        }catch (\Exception $e){
            DB::rollBack();

                $url = explode("/", $stored_image);
                $path = public_path('images').'\\'.end($url);
                unlink($path);

            return response()->json(['error' => $e->getMessage()]);
        }

        return response()->json(['status' => 'success']);
    }

    public function show($id)
    {
        try{
            $category = Category::with(['attributes','children'])->where('id','=',$id)->first();
        }catch (\Exception $e){
            return response()->json(['error' => $e->getMessage()]);
        }

        return response()->json($category);
    }

    public function update(Request $request)
    {
        $oldIndexes = empty($request->oldAttrs) ? [] : array_unique(json_decode($request->oldAttrs));
        $attrs = json_decode($request->attrs);

        if($request->image !== 'null'){
            $validator = Validator::make($request->all(), [
                'image' => 'image'
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors());
            }
        }
        $stored_image = null;

        $category = Category::find($request['id']);
        try{
            DB::beginTransaction();

            if ($request->hasFile('image')) {
                if ($request->file('image')->isValid()) {
                    try{
                        $exc = $request->image->extension();
                        $name = str_random(40).'.'.$exc;
                        $request->file('image')->move(public_path('images/'), $name);
                        $url = url('images/'.$name);
                        $stored_image = $url;

                        $oldUrl = explode("/", $category->image);
                        $oldPath = public_path('images').'\\'.end($oldUrl);
                        unlink($oldPath);
                    }catch (\Exception $e){
                        return response()->json(['error' => $e->getMessage()]);
                    }
                }else{
                    return response()->json(['error' => 'The file is damaged']);
                }
            }

            if($category->name !== $request['name']){
                $validator = Validator::make($request->all(), [
                    'name' => 'required|min:2|max:120|unique:categories',
                ]);

                if ($validator->fails()) {

                    return response()->json($validator->errors());
                }
            }

            $category->name = $request['name'];
            $category->parent_category = (int)$request['parent_category'];
            if($stored_image){
                $category->image = $stored_image;
            }
            $category->save();

            if(count($oldIndexes) > 0) $category->attributes()->detach($oldIndexes);

            if(!empty($attrs)) $category->attributes()->attach($attrs);

            DB::commit();
        }catch (\Exception $e){
            DB::rollBack();

            $url = explode("/", $stored_image);
            $path = public_path('images').'\\'.end($url);
            unlink($path);

            return response()->json(['error' => $e->getMessage()]);
        }

        return response()->json(['status' => 'success']);
    }

    public function destroy(Request $request)
    {
        $cat = Category::where('parent_category', $request['id'])->get();
        $category = Category::find($request->id);
        if((bool)count($cat)){
            return response()->json(['error' => 'В категории есть вложеные категории']);
        }

        try{
            Category::destroy($request['id']);

            $url = explode("/", $category->image);
            $path = public_path('images').'\\'.end($url);
            unlink($path);
        }catch (\Exception $e){
            if(str_contains($e->getMessage(),'Cannot delete or update a parent row')){
                return response()->json(['error' => 'В категории есть продукты!']);
            }
            return response()->json(['error' => $e->getMessage()]);
        }

        return response()->json(['status' => 'success']);

    }

    public function indexFilters($category)
    {
        try{
            $cat = Category::where('name',$category)->pluck('id')->first();
        }catch (\Exception $e){
            return response()->json(['error' => $e->getMessage()]);
        }
        return response()->json($cat);
//        try{
//            $cat = Category::where('name',$category)->pluck('id')->first();
//            $filters = DB::table('products as P')
//                ->join('Product_has_Attribute as PA','PA.Product_id','=','P.id')
//                ->join('attributes as A','A.id','=','PA.Attribute_id')
//                ->join('values as V','V.id','=','PA.Value_id')
//                ->where('P.Category_id','=',(int)$cat)
//                ->select('A.id AS Attribute_id', 'A.name', 'V.id AS Value_id', 'V.value')
//                ->get();
//            if(count($filters) <= 0){
//                $filters = Category::with('attributes')->where('id','=',$cat)->get();
//            }
//        }catch (\Exception $e){
//            return response()->json(['error' => $e->getMessage()]);
//        }
//
//        return response()->json($filters);
//
//        try{
//            $cat = Category::where('name',$category)->pluck('id')->first();
//            $filters = Product::with(['attributes', 'values'])
//                ->where('Category_id',$cat)
//                ->get();
////            if(count($filters) <= 0){
////                $filters = Category::with('attributes')->where('id','=',$cat)->get();
////            }
//        }catch (\Exception $e){
//            return response()->json(['error' => $e->getMessage()]);
//        }
//
//        return response()->json($filters);
//
//
//        try{
//            $cat = Category::where('name',$category)->pluck('id')->first();
//            $prod = Product::with(['category', 'producer'])
//                ->where('Category_id',$cat)
//                ->orderByRaw('amount > 0 DESC, '.$order.' '.$direction)
//                ->paginate($paginate);
//        }catch (\Exception $e){
//            return response()->json(['error' => $e->getMessage()]);
//        }
//        return response()->json(compact('prod'));
    }
}

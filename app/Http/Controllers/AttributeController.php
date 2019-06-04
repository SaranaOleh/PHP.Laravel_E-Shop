<?php

namespace App\Http\Controllers;

use App\Attribute;
use App\AttributeGroups;
use App\Category;
use App\Http\Resources\CategoryAttributeValue;
use App\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AttributeController extends Controller
{
    public function __construct()
    {
        $this->middleware(['ApiAuth', 'ApiIsAdmin'])->except([
            'index','indexGroups','indexFromCategory','show','showGroup'
        ]);
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
            $attributes = Attribute::with('group')->orderBy($order, $direction)->paginate($paginate);
       }catch (\Exception $e){
           return response()->json(['error' => $e->getMessage()]);
       }

       return response()->json(compact('attributes'));
    }

    public function indexGroups()
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
            $groups = AttributeGroups::orderBy($order, $direction)->paginate($paginate);;
        }catch (\Exception $e){
            return response()->json(['error' => $e->getMessage()]);
        }

        return response()->json(compact('groups'));
    }

    public function indexFromCategory($id){
        try{
            $category = Category::with(['attributes','children'])->where('id','=',$id)->first();

            $attr = DB::table('products as P')
                ->join('Product_has_Attribute as PA','PA.Product_id','=','P.id')
                ->join('attributes as A','A.id','=','PA.Attribute_id')
                ->join('values as V','V.id','=','PA.Value_id')
                ->where('P.Category_id','=',(int)$id)
                ->select('A.id AS Attribute_id', 'A.name', 'V.id AS Value_id', 'V.value')
                ->get();
            if(count($attr) <= 0){
                $attr = Category::with('attributes')->where('id','=',$id)->get();
            }
        }catch (\Exception $e){
            return response()->json(['error' => $e->getMessage()]);
        }

        return response()->json(compact(['attr','category']));
    }

    public function storeGroup(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|min:2|max:50|unique:groups',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors());
        }

        try{
            $group = new AttributeGroups;
            $group->name = $request['name'];
            $group->save();
        }catch (\Exception $e){
            return response()->json(['error' => $e->getMessage()]);
        }

        return response()->json(['status' => 'success']);
    }

    public function store(Request $request)
    {
        $group = AttributeGroups::find($request->Group_id);
        if(empty($group)) return response()->json(['error' => 'Создайте группу для аттрибута']);

        $validator = Validator::make($request->all(), [
            'name' => 'required|min:2|max:50|unique:attributes',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors());
        }
        try{
            $attr = new Attribute;
            $attr->name = $request['name'];
            $attr->Group_id = $request['Group_id'];
            $attr->save();
        }catch (\Exception $e){
            return response()->json(['error' => $e->getMessage()]);
        }

        return response()->json(['status' => 'success']);
    }

    public function show($id)
    {
        try{
            $attribute = Attribute::with('group')->where('id','=',$id)->first();
        }catch (\Exception $e){
            return response()->json(['error' => $e->getMessage()]);
        }

        return response()->json($attribute);
    }
    public function showGroup($id)
    {
        try{
            $group = AttributeGroups::where('id','=',$id)->first();
        }catch (\Exception $e){
            return response()->json(['error' => $e->getMessage()]);
        }

        return response()->json($group);
    }

    public function updateGroup(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|min:2|max:50|unique:groups',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors());
        }
        try{
            $group = AttributeGroups::find($request['id']);
            $group->name = $request['name'];
            $group->save();
        }catch (\Exception $e){
            return response()->json(['error' => $e->getMessage()]);
        }

        return response()->json(['status' => 'success']);
    }

    public function update(Request $request)
    {
        try{
            $attr = Attribute::find($request['id']);
            if($attr->name !== $request['name']){
                $validator = Validator::make($request->all(), [
                    'name' => 'required|min:2|max:50|unique:attributes',
                ]);

                if ($validator->fails()) {
                    return response()->json($validator->errors());
                }
            }
            $attr->name = $request['name'];
            $attr->Group_id = $request['Group_id'];
            $attr->save();
        }catch (\Exception $e){
            return response()->json(['error' => $e->getMessage()]);
        }

        return response()->json(['status' => 'success']);
    }

    public function destroyGroup(Request $request)
    {
        try{
            AttributeGroups::destroy($request['id']);
        }catch (\Exception $e){
            if(str_contains($e->getMessage(),'Cannot delete or update a parent row')){
                return response()->json(['error' => 'В группе есть аттрибуты!']);
            }
            return response()->json(['error' => $e->getMessage()]);
        }

        return response()->json(['status' => 'success']);
    }

    public function destroy(Request $request)
    {
        try{
            Attribute::destroy($request['id']);
        }catch (\Exception $e){
            return response()->json(['error' => $e->getMessage()]);
        }
        return response()->json(['status' => 'success']);
    }
}

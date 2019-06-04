<?php

namespace App\Http\Controllers;

use App\Product;
use App\Raiting;
use App\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ReviewController extends Controller
{
    public function __construct()
    {
        $this->middleware('ApiAuth')->only('store');
        $this->middleware(['ApiAuth', 'ApiIsAdmin'])->except(['index', 'show', 'store']);
    }

    public function index()
    {
        //
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'text' => 'required|string',
            'name' => 'required|string',
            'Users_id' => 'required|numeric',
            'Products_id' => 'required|numeric',
            'value' => 'required|numeric'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors());
        }

        try{
            DB::beginTransaction();

            $review = new Review();
            $review->text = $request['text'];
            $review->Users_id = (int)$request['Users_id'];
            $review->name = $request['name'];
            $review->Products_id = (int)$request['Products_id'];
            $review->value = (int)$request['value'];
            $review->save();

            if($request->has('raiting')){
                $raiting = new Raiting();
                $raiting->Product_id = (int)$request['Products_id'];
                $raiting->User_id = (int)$request['Users_id'];
                $raiting->raiting = (int)$request->raiting;
                $raiting->save();

                $totalRaiting = Raiting::where('Product_id',(int)$request['Products_id'])->avg('raiting');

                $product = Product::where('id',(int)$request['Products_id'])->first();
                $product->raiting = round($totalRaiting,2);
                $product->save();
            }

            DB::commit();

        }catch (\Exception $e){
            DB::rollBack();

            if(str_contains($e->getMessage(),' Duplicate entry')){
                return response()->json(['error' => 'Вы уже оставляли отзыв об этом товаре!']);
            }
            return response()->json(['error' => $e->getMessage()]);
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}

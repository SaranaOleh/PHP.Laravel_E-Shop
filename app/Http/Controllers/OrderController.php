<?php

namespace App\Http\Controllers;

use App\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{

    public function __construct()
    {
        $this->middleware('ApiAuth')->only('store');
        $this->middleware(['ApiAuth', 'ApiIsAdmin'])->except(['index', 'show']);

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
            $orders = Order::with([])
                ->orderBy($order, $direction)
                ->paginate($paginate);
        }catch (\Exception $e){
            return response()->json(['error' => $e->getMessage()]);
        }

        return response()->json(compact('orders'));
    }

    public function store(Request $request)
    {
        $products = json_decode($request->products);

        $attachedProducts = [];
        foreach ($products as $item){
            $attachedProducts[(int)$item->id] = ['amount' => (int)$item->amount];
        }

        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric',
            'price' => 'required|numeric',
            'user' => 'required|exists:users,id',
            'comment' => 'string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors());
        }

        try{
            DB::beginTransaction();

            $order = new Order;
            $order->status = 'processing';
            $order->price = (int)$request['price'];
            $order->amount = (int)$request['amount'];
            if(isset($request->comment)) $order->comment = $request['comment'];
            $order->User_id = (int)$request['user'];
            $order->save();

            $order->products()->attach($attachedProducts);

            DB::commit();
        }catch (\Exception $e){
            DB::rollBack();

            return response()->json(['error' => $e->getMessage()]);
        }

        return response()->json(['status' => 'success','order' => $order->id]);
    }

    public function show($id)
    {
        try{
            $orders = Order::with(['products','user'])->where('id','=',$id)->first();
        }catch (\Exception $e){
            return response()->json(['error' => $e->getMessage()]);
        }

        return response()->json($orders);
    }

    public function update(Request $request)
    {
        $products = json_decode($request->products);

        $attachedProducts = [];

        foreach ($products as $item){
            $attachedProducts[(int)$item->id] = ['amount' => (int)$item->amount];
        }

        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric',
            'price' => 'required|numeric',
            'user' => 'required|exists:users,id',
            'comment' => 'string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors());
        }

        try{
            DB::beginTransaction();

            $order = Order::find($request['oldOrder']);

            $oldProducts = $order->products()->get()->pluck('id')->toArray();

            $order->products()->detach($oldProducts);

            $order->status = 'delivery';
            $order->price = (int)$request['price'];
            $order->amount = (int)$request['amount'];
            $order->save();

            $order->products()->attach($attachedProducts);

            DB::commit();
        }catch (\Exception $e){
            DB::rollBack();

            return response()->json(['error' => $e->getMessage()]);
        }

        return response()->json(['status' => 'success']);
    }

    public function destroy(Request $request)
    {

        try{
            Order::destroy($request['id']);

        }catch (\Exception $e){

            return response()->json(['error' => $e->getMessage()]);
        }

        return response()->json(['status' => 'success']);
    }
}

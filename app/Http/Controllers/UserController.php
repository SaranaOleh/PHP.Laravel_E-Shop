<?php

namespace App\Http\Controllers;

use App\Role;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware(['ApiAuth', 'ApiIsAdmin'])->except(['index','show','indexRole']);
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
            $paginate = 0;
        }

        try{
            $user = User::orderBy($order, $direction)->paginate($paginate);
        }catch (\Exception $e){
            return response()->json(['error' => $e->getMessage()]);
        }

        return response()->json(compact('user'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'surname' => 'required|alpha|min:2|max:22',
            'name' => 'required|alpha|min:2|max:22',
            'secondname' => 'required|alpha|min:2|max:22',
            'phone' => 'required|alpha_dash|unique:users',
            'email' => 'required|string|email|max:250|unique:users',
            'password'=> 'required|alpha_dash'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors());
        }
        try{
            $user = new User;
            $user->surname = $request['surname'];
            $user->name = $request['name'];
            $user->secondname = $request['secondname'];
            $user->phone = $request['phone'];
            $user->email = $request['email'];
            $user->password = bcrypt($request['password']);
            $user->save();

            $user->roles()->attach(2);
        }catch (\Exception $e){
            return response()->json(['error' => $e->getMessage()]);
        }

        return response()->json(['status' => 'success']);
    }

    public function show($id)
    {
        try{
            $user = User::with('roles')->where('id','=',$id)->first();
            $roles = Role::all();
        }catch (\Exception $e){
            return response()->json(['error' => $e->getMessage()]);
        }

        return response()->json(['user' => $user,'roles' => $roles]);
    }

    public function update(Request $request)
    {
        $user = User::find($request->id);
        $role = $user->roles()->get()->pluck('id');
        $newPassword = null;
        $newRole = null;

        if($role[0] !== (int)$request->role) $newRole = (int)$request->role;

        if($user->email !== $request->email){
            $validator = Validator::make($request->all(), [
                'email' => 'required|string|email|max:250|unique:users',
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors());
            }
        }
        if($user->phone !== $request->phone){
            $validator = Validator::make($request->all(), [
                'phone' => 'required|alpha_dash|unique:users',
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors());
            }
        }
        if(!empty($request->newPassword)){
            $validator = Validator::make($request->all(), [
                'newPassword'=> 'required|alpha_dash'
            ]);
            $newPassword = $request->newPassword;

            if ($validator->fails()) {
                return response()->json($validator->errors());
            }
        }
        $validator = Validator::make($request->all(), [
            'surname' => 'required|alpha|min:2|max:22',
            'name' => 'required|alpha|min:2|max:22',
            'secondname' => 'required|alpha|min:2|max:22',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors());
        }
        try{
            $user->surname = $request['surname'];
            $user->name = $request['name'];
            $user->secondname = $request['secondname'];
            $user->phone = $request['phone'];
            $user->email = $request['email'];

            if($newPassword) $user->password = bcrypt($newPassword);

            $user->save();

            if(!empty($newRole)){
                $user->roles()->attach((int)$newRole);
                $user->roles()->detach($role);
            }

        }catch (\Exception $e){
            return response()->json(['error' => $e->getMessage()]);
        }

        return response()->json(['status' => 'success']);
    }


    public function destroy(Request $request)
    {
        try{
            User::destroy($request['id']);
        }catch (\Exception $e){
            return response()->json(['error' => $e->getMessage()]);
        }

        return response()->json(['status' => 'success']);
    }
}

<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenBlacklistedException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Facades\JWTAuth;

class ApiAuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('ApiAuth')->only(['isAuth','user']);
        $this->middleware(['ApiAuth', 'ApiIsAdmin'])->only('isAdmin');
    }

    public function login(Request $request)
    {
        try{
            $this->validate(request(),[
                'email' => 'required|string|email|max:250',
                'password'=> 'required|alpha_dash'
            ]);

            $credentials = $request->only('email', 'password');

            if (! $token = JWTAuth::attempt($credentials)) {
                return response()->json(['error' => 'не верные данные']);
            }
        }catch (\Exception $e){
            return response()->json(['error' => $e->getMessage()]);
        }

        return response()->json(compact('token'));

    }

    public function register(Request $request)
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

        $user = new User;
        $user->surname = $request['surname'];
        $user->name = $request['name'];
        $user->secondname = $request['secondname'];
        $user->phone = $request['phone'];
        $user->email = $request['email'];
        $user->password = bcrypt($request['password']);
        $user->save();

        $user->roles()->attach(2);
        return response()->json(['status' => 'success']);
    }

    public function logout(Request $request)
    {
        try{
            JWTAuth::parseToken()->invalidate();
        }catch (\Exception $e){
            return response()->json(['error' => $e->getMessage()]);
        }

        return response()->json(['status' => 'success']);

    }

    public function isAdmin()
    {
        return response()->json(['allowed' => true]);
    }

    public function isAuth()
    {
        return response()->json(['allowed' => true]);
    }



    public function loginAdmin(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:250',
            'password'=> 'required|alpha_dash'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors());
        }

        $credentials = $request->only('email', 'password');

        try{
            JWTAuth::parseToken()->invalidate();
        }catch (TokenInvalidException $e){

        }

        if (! $token = JWTAuth::attempt($credentials)) {
            return response()->json(['error' => 'не верные данные']);
        }

        $user = JWTAuth::toUser($token);

        try{
            if($user->roles[0]->name !== 'admin'){
                return response()->json(['error' => "access denied for {$user->email}"]);
            }
        }catch (\Exception $e){
            return response()->json(['error' => "access denied for {$user->email}"]);
        }


        return response()->json(compact('token'));
    }

    public function user(){
        try{
            $user = JWTAuth::parseToken()->authenticate();
        }catch (\Exception $e){
            return response()->json(['error' => $e->getMessage()]);
        }

        return response()->json(compact('user'));
    }
}


<?php

use Illuminate\Http\Request;


//Route::middleware('jwt.auth')->post('user/isAdmin', function(Request $request) {
//    return auth()->user();
//});
//Route::post('user/isAdmin', function () {
//    return response()->json("kjdfkdljf");
//})->middleware('jwt.auth');

Route::prefix('auth')->group(function () {
    Route::post('', 'ApiAuthController@user');
    Route::post('register', 'ApiAuthController@register');
    Route::post('login', 'ApiAuthController@login');
    Route::post('logout', 'ApiAuthController@logout');
    Route::post('isAuth', 'ApiAuthController@isAuth');
    Route::post('isAdmin', 'ApiAuthController@isAdmin');
    Route::post('loginAdmin', 'ApiAuthController@loginAdmin');
    Route::post('category', 'ApiAuthController@all');
});

Route::prefix('category')->group(function () {
    Route::get('/', 'CategoryController@index');
    Route::get('/filters/{category}', 'CategoryController@indexFilters');
    Route::get('/{id}', 'CategoryController@show');
    Route::post('add', 'CategoryController@store');
    Route::delete('del', 'CategoryController@destroy');
    Route::post('update', 'CategoryController@update');
});

Route::prefix('producer')->group(function () {
    Route::get('/', 'ProducerController@index');
    Route::get('/{id}', 'ProducerController@show');
    Route::post('add', 'ProducerController@store');
    Route::delete('del', 'ProducerController@destroy');
    Route::post('update', 'ProducerController@update');
});

Route::prefix('attribute')->group(function () {
    Route::get('groups', 'AttributeController@indexGroups');
    Route::get('group/{id}', 'AttributeController@showGroup');
    Route::post('addGroup', 'AttributeController@storeGroup');
    Route::delete('delGroup', 'AttributeController@destroyGroup');
    Route::patch('updateGroup', 'AttributeController@updateGroup');

    Route::get('/', 'AttributeController@index');
    Route::get('/{id}', 'AttributeController@show');
    Route::post('add', 'AttributeController@store');
    Route::delete('del', 'AttributeController@destroy');
    Route::patch('update', 'AttributeController@update');

    Route::get('fromCategory/{id}', 'AttributeController@indexFromCategory');
});

Route::prefix('product')->group(function () {
    Route::get('/', 'ProductController@index');
    Route::get('/filters/{category}', 'ProductController@indexFilters');
    Route::get('/{id}', 'ProductController@show');
//    Route::get('counts', 'ProductController@indexCounts');
    Route::get('search/{value}', 'ProductController@indexSearch');
    Route::post('add', 'ProductController@store');
    Route::delete('del', 'ProductController@destroy');
    Route::post('update', 'ProductController@update');
});

Route::prefix('user')->group(function () {
    Route::get('/', 'UserController@index');
    Route::get('/{id}', 'UserController@show');
    Route::post('add', 'UserController@store');
    Route::delete('del', 'UserController@destroy');
    Route::post('update', 'UserController@update');
});

Route::prefix('order')->group(function () {
    Route::get('/', 'OrderController@index');
    Route::get('/{id}', 'OrderController@show');
    Route::post('add', 'OrderController@store');
    Route::delete('del', 'OrderController@destroy');
    Route::post('update', 'OrderController@update');
});

Route::prefix('review')->group(function () {
    Route::get('/', 'ReviewController@index');
    Route::get('/{id}', 'ReviewController@show');
    Route::post('add', 'ReviewController@store');
    Route::delete('del', 'ReviewController@destroy');
    Route::post('update', 'ReviewController@update');
});
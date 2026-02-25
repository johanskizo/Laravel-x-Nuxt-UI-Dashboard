<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::prefix('user')
	->middleware('auth:api')
	->group(function () {
		Route::get('/data', 'UserController@index');
		Route::get('/role/options', 'UserController@roleOptionsData');
		Route::get('/show/{id}', 'UserController@show');
		Route::put('/update/{id}', 'UserController@update');
		Route::post('/bulk-delete', 'UserController@bulkDelete');
	});

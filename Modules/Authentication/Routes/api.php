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

Route::prefix('authentication')->group(function () {
	Route::post('/login', 'AuthenticationController@login');
	Route::post('/signup', 'AuthenticationController@signup');
	Route::post('/forgot-password', 'AuthenticationController@forgotPassword');
	Route::post('/reset-password', 'AuthenticationController@resetPassword');
	Route::middleware('auth:api')->group(function () {
		Route::get('/user', 'AuthenticationController@user');
		Route::post('/logout', 'AuthenticationController@logout');
	});
});

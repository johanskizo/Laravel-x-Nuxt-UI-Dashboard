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

Route::prefix('profile')
	->middleware('auth:api')
	->group(function () {
		Route::get('/show/{user_id}', 'ProfileController@show');
		Route::post('/save/{user_id}', 'ProfileController@save');
		Route::get('/user/show/{id}', 'ProfileController@showUser');
		Route::put('/user/update/{id}', 'ProfileController@updateUser');
		Route::get('/security/show/{id}', 'ProfileController@showSecurity');
		Route::put('/security/update-password/{id}', 'ProfileController@updatePassword');
		Route::delete('/security/session-logout/{tokenn_id}', 'ProfileController@logoutSession');
		Route::post('/settings/save/{user_id}', 'ProfileController@saveSettings');
	});

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

Route::prefix('dashboard')
	->middleware('auth:api')
	->group(function () {
		Route::get('/uptime', 'DashboardController@getUptime');
		Route::get('/cpu', 'DashboardController@getCpu');
		Route::get('/ram', 'DashboardController@getRam');
		Route::get('/disk', 'DashboardController@getDisk');
		Route::get('/services', 'DashboardController@getServices');
		Route::get('/logs', 'DashboardController@getLogs');
	});

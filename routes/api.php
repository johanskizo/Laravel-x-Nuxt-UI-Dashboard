<?php

use Illuminate\Http\Request;
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

Route::get('/', function (Request $request) {
	return response()->json([
		'app' => config('app.name'),
		'version' => app()->version(),
		'php' => PHP_VERSION,
		'status' => 'Connected',
		'message' => 'Welcome to the API Service'
	]);
});

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

Route::prefix('privilege')
	->middleware('auth:api')
	->group(function () {
		Route::prefix('role')->group(function () {
			Route::get('/data', 'RoleController@index')->middleware('permission:Privilege.role.view');
			Route::post('/store', 'RoleController@store')->middleware('permission:Privilege.role.add');
			Route::get('/show/{id}', 'RoleController@show')->middleware('permission:Privilege.role.view');
			Route::put('/update/{id}', 'RoleController@update')->middleware('permission:Privilege.role.edit');
			Route::post('/bulk-delete', 'RoleController@bulkDelete')->middleware('permission:Privilege.role.delete');
			Route::get('/user/options/{id}', 'RoleController@userOptionsData');
			Route::get('/user/data/{id}', 'RoleController@userData')->middleware('permission:Privilege.role.user.view');
			Route::post('/user/store/{id}', 'RoleController@userStore')->middleware('permission:Privilege.role.user.add');
			Route::post('/user/bulk-delete/{id}', 'RoleController@userBulkDelete')->middleware(
				'permission:Privilege.role.user.delete'
			);
			Route::get('/permission/options', 'RoleController@permissionOptions');
			Route::get('/permission/show/{id}', 'RoleController@permissionShow')->middleware(
				'permission:Privilege.role.permission.view'
			);
			Route::put('/permission/update/{id}', 'RoleController@permissionUpdate')->middleware(
				'permission:Privilege.role.permission.edit'
			);
		});
		Route::prefix('permission')->group(function () {
			Route::get('/data', 'PermissionController@index')->middleware('permission:Privilege.permission.view');
			Route::post('/store', 'PermissionController@store')->middleware('permission:Privilege.permission.add');
			Route::get('/show/{id}', 'PermissionController@show')->middleware('permission:Privilege.permission.view');
			Route::put('/update/{id}', 'PermissionController@update')->middleware('permission:Privilege.permission.edit');
			Route::post('/bulk-delete', 'PermissionController@bulkDelete')->middleware(
				'permission:Privilege.permission.delete'
			);
		});
	});

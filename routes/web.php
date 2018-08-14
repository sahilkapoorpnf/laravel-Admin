<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();
 Route::group(['middleware' => ['web']], function () {
    Route::get('/home', 'HomeController@index')->name('home');
    
     Route::group(['middleware' => ['is_admin']], function () {
            Route::get('/admin', 'AdminController@admin')->name('admin');
            Route::match(['get','post'],'admin/admin-profile/{id}', 'AdminController@admin_profile');
            Route::match(['get','post'],'admin/admin-setting/{id}', 'AdminController@admin_setting');
            Route::match(['get','post'],'admin/add-user', 'AdminController@add_user');
            Route::match(['get','post'],'admin/manage-users', 'AdminController@manage_users');
            Route::match(['get','post'],'admin/update-users-status/{id}', 'AdminController@update_users_status');
            Route::match(['get','post'],'admin/edit-user/{id}', 'AdminController@edit_user');
            Route::match(['get','post'],'admin/delete-user/{id}', 'AdminController@delete_user');
            
        });
 });

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
            Route::match(['get','post'],'admin/manage-stylists', 'AdminController@manage_users');
            Route::match(['get','post'],'admin/stylist-bookings/{id}', 'AdminController@stylist_bookings');
            Route::match(['get','post'],'admin/update-users-status/{id}', 'AdminController@update_users_status');
            Route::match(['get','post'],'admin/edit-user/{id}', 'AdminController@edit_user');
            Route::match(['get','post'],'admin/delete-user/{id}', 'AdminController@delete_user');
            
            Route::match(['get','post'],'admin/add-user-type', 'AdminController@add_user_type');
            Route::match(['get','post'],'admin/manage-users-type', 'AdminController@manage_users_type');
            Route::match(['get','post'],'admin/update-users-type-status/{id}', 'AdminController@update_users_type_status');
            Route::match(['get','post'],'admin/edit-user-type/{id}', 'AdminController@edit_user_type');
            Route::match(['get','post'],'admin/delete-user-type/{id}', 'AdminController@delete_user_type');
            
            Route::match(['get','post'],'admin/add-category', 'AdminController@add_category');
            Route::match(['get','post'],'admin/manage-category', 'AdminController@manage_category');
            Route::match(['get','post'],'admin/update-category-status/{id}', 'AdminController@update_category_status');
            Route::match(['get','post'],'admin/edit-category/{id}', 'AdminController@edit_category');
            Route::match(['get','post'],'admin/delete-category/{id}', 'AdminController@delete_category');
            
            Route::match(['get','post'],'admin/add-terms', 'AdminController@add_terms');
            Route::match(['get','post'],'admin/manage-terms', 'AdminController@manage_terms');
            Route::match(['get','post'],'admin/update-terms-status/{id}', 'AdminController@update_terms_status');
            Route::match(['get','post'],'admin/edit-terms/{id}', 'AdminController@edit_terms');
            Route::match(['get','post'],'admin/delete-terms/{id}', 'AdminController@delete_terms');
            
            Route::match(['get','post'],'admin/manage-bookings', 'AdminController@manage_bookings');
            Route::match(['get','post'],'admin/view-booking/{id}', 'AdminController@view_booking');
            Route::match(['get','post'],'admin/delete-booking/{id}', 'AdminController@delete_booking');
            
            Route::match(['get','post'],'admin/update-flash-screen', 'AdminController@update_flash_screen');
            Route::match(['get','post'],'admin/update-refer-amount', 'AdminController@update_refer_amount');
            Route::match(['get','post'],'admin/admin-booking-amount', 'AdminController@admin_booking_amount');
        });
 });

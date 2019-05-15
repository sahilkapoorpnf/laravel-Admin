<?php

use Illuminate\Http\Request;

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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
Route::match(['get','post'],'/flash-screen', 'FlashController@flash_data');
Route::match(['get','post'],'/categories', 'ApiController@categories');
Route::match(['get','post'],'/signup', 'ApiController@signUp');
Route::match(['get','post'],'/login', 'ApiController@login');
Route::match(['get','post'],'/update-password', 'ApiController@update_password');
Route::match(['get','post'],'/forget-password', 'ApiController@forget_password');
Route::match(['get','post'],'/user-detail', 'ApiController@user_detail');
Route::match(['get','post'],'/update-profile', 'ApiController@update_profile');
Route::match(['get','post'],'/user-status', 'ApiController@user_status');
Route::match(['get','post'],'/term-conditions', 'ApiController@term_conditions');
Route::match(['get','post'],'/explore', 'ApiController@explore');
Route::match(['get','post'],'/rating', 'ApiController@rating');
Route::match(['get','post'],'/search-suggestions', 'ApiController@search_suggestions');
Route::match(['get','post'],'/search-step1', 'ApiController@search_step1');
Route::match(['get','post'],'/search-final-step', 'ApiController@search_final_step');
Route::match(['get','post'],'/category-data', 'ApiController@category_data');
Route::match(['get','post'],'/book-stylist', 'ApiController@book_stylist');
Route::match(['get','post'],'/booking-accept-status', 'ApiController@booking_accept_status');
Route::match(['get','post'],'/booking-accept-time', 'ApiController@booking_accept_time');
Route::match(['get','post'],'/booking-chat-list', 'ApiController@booking_chat_list');
Route::match(['get','post'],'/save-post-stylist', 'ApiController@save_post_stylist');
Route::match(['get','post'],'/saved-stylist', 'ApiController@saved_stylist');
Route::match(['get','post'],'/filter-stylist', 'ApiController@filter_stylist');
Route::match(['get','post'],'/first-payment', 'ApiController@first_payment');
Route::match(['get','post'],'/my-bills', 'ApiController@my_bills');
Route::match(['get','post'],'/my-meetings', 'ApiController@my_meetings');
Route::match(['get','post'],'/single-bill', 'ApiController@single_bill');
Route::match(['get','post'],'/upload-bills', 'ApiController@upload_bills');
Route::match(['get','post'],'/bills-listing', 'ApiController@bills_listing');
Route::match(['get','post'],'/meeting-status', 'ApiController@meeting_status');
Route::match(['get','post'],'/end-exteded-meeting', 'ApiController@end_exteded_meeting');
Route::match(['get','post'],'/payment-history', 'ApiController@payment_history');
Route::match(['get','post'],'/clear-history', 'ApiController@clear_history');
Route::match(['get','post'],'/stylist-banner', 'ApiController@stylist_banner');
Route::match(['get','post'],'/page-earn', 'ApiController@page_earn');
Route::match(['get','post'],'/notifications', 'ApiController@notifications');
Route::match(['get','post'],'/calandar-data', 'ApiController@calandar_data');
Route::match(['get','post'],'/all-near-stylist', 'ApiController@all_near_stylist');
Route::match(['get','post'],'/all-picked-stylist', 'ApiController@all_picked_stylist');
Route::match(['get','post'],'/all-popular-stylist', 'ApiController@all_popular_stylist');
Route::match(['get','post'],'/user-wallet', 'ApiController@user_wallet');
Route::match(['get','post'],'/stylist-wallet', 'ApiController@stylist_wallet');
Route::match(['get','post'],'/user-wallet-balance', 'ApiController@user_wallet_balance');
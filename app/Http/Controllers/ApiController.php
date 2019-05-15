<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use Validator;
use Hash;
use Illuminate\Support\Facades\Input;
use DB;
use Image;
use App\Flash;
use App\User;
use App\UserType;
use App\Category;
use App\UserSpecialty;
use App\Term;
use App\ShoppingLocation;

class ApiController extends Controller {

    public function __construct() {
        
    }

    //Get Categories Data START
    public function categories(Request $request) {
        $data = DB::table('categories')->where('status', '1')->get();
        foreach ($data as $datas) {
            if (isset($datas->image) && !empty($datas->image)) {
                $image_data = url('../storage/category_files/') . '/' . $datas->image;
            } else {
                $image_data = '';
            }
            $resultData[] = array(
                'id' => $datas->id,
                'cat_name' => $datas->title,
                'image' => $image_data,
            );
        }

        return json_encode(array('status' => "200", 'message' => 'categories data', 'categories' => $resultData));
    }

    //Get Categories Data END
    //Signup START
    public function signUp(Request $request) {
        $res = array();
        if (!empty($request->input('name')) && !empty($request->input('email')) && !empty($request->input('password'))) {
//                $length_of_string =6;
//                $str_result = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';             
//                return substr(str_shuffle($str_result),0, $length_of_string); 

            $users = $user = DB::table('users')
                    ->where('email', $request->input('email'))
                    ->where('phone', $request->input('phone'))
                    ->where('type', $request->input('user_type'))
                    ->first();

            if (!empty($users)) {
                $res['status'] = "201";
                $res['msg'] = "User with this email is already exist.";
                return json_encode($res);
            } else {
                $pass = Hash::make($request->input('password'));
                $token = Hash::make(now());
                if ($file = $request->hasFile('image')) {
                    $file = $request->file('image');
                    $imageType = $file->getClientmimeType();
                    // print_r($imageType); die;
                    $rules = array(
                        'image' => 'mimes:jpeg,jpg,png',
                    );

                    $validator = Validator::make($request->all(), $rules);

                    if ($validator->fails()) {
                        $res['status'] = "201";
                        $res['msg'] = "Image format must be jpeg,jpg,png";
                        return json_encode($res);
                    } else {
                        $fileName = $file->getClientOriginalName();
                        $fileNameUnique = time() . '_' . $fileName;
                        $destinationPath = storage_path() . '/files/';
                        $file->move($destinationPath, $fileNameUnique);
                    }
                    $imageData = $fileNameUnique;
                } else {
                    $imageData = '';
                }

                //print_r($imageData); die
                $unique_refer_code = substr(uniqid(rand(), true), 6, 6);

                if ($request->input('refer_code') != '') {

                    $set_refered_amount = DB::table('set_refer_amount')->where('id', '1')->first();
                    $user_refered_data = DB::table('users')->where('refer_code', $request->input('refer_code'))->first();
                    if (isset($user_refered_data)) {
                        $user = DB::table('users')->insertGetId([
                            'name' => $request->input('name'),
                            'last_name' => $request->input('last_name'),
                            'phone' => $request->input('phone'),
                            'email' => $request->input('email'),
                            'gender' => $request->input('gender'),
                            'type' => $request->input('user_type'),
                            'device_token' => $request->input('device_token'),
                            'device_type' => $request->input('device_type'),
                            'user_price' => $request->input('user_price'),
                            'refer_code' => $unique_refer_code,
                            'image' => $imageData,
                            'password' => $pass,
                            'accessToken' => $token
                                ]
                        );
                        DB::table('wallet')->insert([
                            'user_id' => $user,
                            'amount' => $set_refered_amount->amount,
                        ]);
                        DB::table('wallet')->insert([
                            'user_id' => $user_refered_data->id,
                            'amount' => $set_refered_amount->amount,
                        ]);
                    } else {
                        $res['status'] = "201";
                        $res['msg'] = "Invalid referal code";
                        return json_encode($res);
                    }
                } else {
                    $user = DB::table('users')->insertGetId([
                        'name' => $request->input('name'),
                        'last_name' => $request->input('last_name'),
                        'phone' => $request->input('phone'),
                        'email' => $request->input('email'),
                        'gender' => $request->input('gender'),
                        'type' => $request->input('user_type'),
                        'device_token' => $request->input('device_token'),
                        'device_type' => $request->input('device_type'),
                        'user_price' => $request->input('user_price'),
                        'refer_code' => $unique_refer_code,
                        'image' => $imageData,
                        'password' => $pass,
                        'accessToken' => $token
                            ]
                    );
                }


                if ($request->input('shopping_location')) {
                    $shoping_loc = json_decode($request->input('shopping_location'));
                    foreach ($shoping_loc as $shoping_locs) {
                        $childId = DB::table('shopping_locations')->insertGetId([
                            'user_id' => $user,
                            'lat' => $shoping_locs->lat,
                            'lng' => $shoping_locs->lng,
                            'city' => $shoping_locs->city
                                ]
                        );
                    }
                }

                if ($request->input('specialties')) {
                    $specialties = json_decode($request->input('specialties'));
                    foreach ($specialties as $specialtie) {
                        $childId = DB::table('user_specialties')->insertGetId([
                            'user_id' => $user,
                            'cat_id' => $specialtie->cat_id
                                ]
                        );
                    }
                }

                if ($user) {
                    $userGetData = DB::table('users')
                            ->where('id', $user)
                            ->first();

                    $res['id'] = $userGetData->id;
                    $res['firstname'] = $userGetData->name;
                    $res['email'] = $userGetData->email;
                    $res['phone'] = $userGetData->phone;
                    $res['type'] = $userGetData->type;
                    $res['refer_code'] = $userGetData->refer_code;
                    $res['accessToken'] = $token;

                    $res['status'] = "200";
                    $res['msg'] = "User created successfully.";
                } else {
                    $res['status'] = "201";
                    $res['msg'] = "Something went wrong, please try again.";
                }

                return json_encode($res);
            }
        } else {
            $res['status'] = "201";
            $res['msg'] = "Email, name or password missing";

            return json_encode($res);
        }
    }

    //Signup END
    //Login START
    public function login(Request $request) {

        $res = array();
        if (!empty($request->input('email')) && !empty($request->input('password'))) {
            // return $users = DB::table('users')->get();
            $users = $user = DB::table('users')
                    ->where('email', $request->input('email'))
                    ->orWhere('phone', $request->input('email'))
                    ->where('type', $request->input('user_type'))
                    ->first();

            if (isset($users) && !empty($users)) {

                if (Hash::check($request->input('password'), $user->password)) {
                    $token = Hash::make(now());

                    // $pass = Hash::make($request->input('password'));
                    // $res['pass'] = Hash::check($request->input('password'), $pass); 

                    DB::table('users')
                            ->where('email', $request->input('email'))
                            ->orWhere('phone', $request->input('email'))
                            ->update(['accessToken' => $token, 'device_token' => $request->input('device_token'), 'device_type' => $request->input('device_type')]);
                    if (isset($user->phone) && !empty($user->phone)) {
                        $user_phone = $user->phone;
                    } else {
                        $user_phone = '';
                    }

                    $res['id'] = $user->id;
                    $res['firstname'] = $user->name;
                    $res['email'] = $user->email;
                    $res['phone'] = $user_phone;
                    $res['type'] = $user->type;
                    $res['refer_code'] = $user->refer_code;
                    $res['accessToken'] = $token;
                    $res['msg'] = "Success";
                    $res['status'] = "200";

                    return json_encode($res);
                } else {
                    $res['status'] = "201";
                    $res['msg'] = "Wrong Password.";
                    return json_encode($res);
                }
            } else {
                $res['status'] = "201";
                $res['msg'] = "Wrong Email Id.";

                return json_encode($res);
            }
        } else {
            $res['status'] = "201";
            $res['msg'] = "Email or password missing";

            return json_encode($res);
        }
    }

    //Login END
    //Get user detail START
    public function user_detail(Request $request) {
        if (!empty($request->input('user_id'))) {
            $user_id = $request->input('user_id');
            $logged_in = $request->input('logged_in');
            $user_count = User::where('id', $user_id)->count();
            if ($user_count != '0') {
                $stylist_view_count_data = User::where('id', $user_id)->first();
                $prev_view_count = $stylist_view_count_data->view_count;
                $new_view_count = $prev_view_count + 1;
                if ($logged_in != $user_id) {
                    User::where('id', $user_id)->update([
                        'view_count' => $new_view_count,
                    ]);
                }
                $user_datas = User::where('id', $user_id)->with('shopping_locations', 'user_speciality')->first();
                // print_r($user_datas); die;
                //  foreach ($user_data as $user_datas) {
                if (isset($user_datas->image) && !empty($user_datas->image)) {
                    $image_data = url('../storage/files/') . '/' . $user_datas->image;
                } else {
                    $image_data = '';
                }

                if (count($user_datas['user_speciality']) == '' || count($user_datas['user_speciality']) == '0') {
                    $speciality_data = array();
                } else {
                    foreach ($user_datas['user_speciality'] as $user_speciality) {
                        $cat_data = Category::where('id', $user_speciality->cat_id)->first();
                        $speciality_data[] = array(
                            'id' => $user_speciality->id,
                            'cat_id' => $user_speciality->cat_id,
                            'cat_name' => $cat_data->title,
                        );
                    }
                }

                if (count($user_datas['shopping_locations']) == '' || count($user_datas['shopping_locations']) == '0') {
                    $loc_data = array();
                } else {
                    foreach ($user_datas['shopping_locations'] as $shoping_loc) {
                        $loc_data[] = array(
                            'id' => $shoping_loc->id,
                            'lat' => $shoping_loc->lat,
                            'lng' => $shoping_loc->lng,
                            'city' => $shoping_loc->city,
                        );
                    }
                }



                $get_rating_data = DB::table('ratings')->select('rate')->where('stylist_id', $user_datas->id)->get();
                $get_data_rate_total = 0;
                foreach ($get_rating_data as $get_rating_datas) {
                    $get_data_rate_total += $get_rating_datas->rate;
                }

                if (count($get_rating_data) != '0') {
                    $average = $get_data_rate_total / count($get_rating_data);
                    $count_rating = count($get_rating_data);
                } else {
                    $average = '0';
                    $count_rating = '0';
                }
                $get_rating3 = DB::table('ratings')->where('rate', '3')->where('stylist_id', $user_datas->id)->count();
                $get_rating4 = DB::table('ratings')->where('rate', '4')->where('stylist_id', $user_datas->id)->count();
                $get_rating5 = DB::table('ratings')->where('rate', '5')->where('stylist_id', $user_datas->id)->count();
                // echo $request->input('logged_in');
                // echo $request->input('stylist_id');
                $saved_count_check = DB::table('saved_stylist')->where('user_id', $request->input('logged_in'))->where('stylist_id', $request->input('user_id'))->where('status', '1')->get();
                //print_r(count($saved_count_check)); die;
                if (count($saved_count_check) != '0') {
                    $saved_check = '1';
                } else {
                    $saved_check = '0';
                }

                //get stylist working hours START
                $first_total_hours = DB::table('bookings')->where('stylist_id', $user_id)->where('meeting_status', 'ended')->count();

                if ($first_total_hours != '0') {
                    $total_bookings = DB::table('bookings')->where('stylist_id', $user_id)->where('meeting_status', 'ended')->get();

//                    print_r($total_bookings); die;
//                   $total_times = [];
                    foreach ($total_bookings as $total_booking) {
                        $re_book = DB::table('meeting_rescheduled')->where('booking_id', $total_booking->id)->get();
                        if (count($re_book) == '0') {
                            $total_count_found = count($total_bookings);
                            $time_check = strtotime("00:60:00");
                            $total_worked_time1 = $time_check * count($total_bookings);
                            $total_worked_time = date('H:i:s', $total_worked_time1);
//                            $get_total_hours=$total_count_found -1;
//                             $total_hours = date('H', $get_total_hours);
//                            $hours = floor($get_total_hours / 60);
                            // $time = strtotime($time_check);
                            $minutes = $total_count_found * 60;
                            $endTime = date("H:i:s", strtotime('+' . $minutes . 'minutes', $time_check));
                            $total_worked_time = "$endTime";
                        } else {
                            foreach ($re_book as $re_books) {
                                $total_times[] = (array) $re_books;
                            }
                        }
//                       
                    }

//                   echo date('H:i:s',$total_worked_time); die;
                    if (isset($total_times) && !empty($total_times)) {
                        $seconds = 0;
                        foreach ($total_times as $array_timess) {
                            list($hour, $minute, $second) = explode(':', $array_timess['time']);
                            $seconds += $hour * 3600;
                            $seconds += $minute * 60;
                            $seconds += $second;
                        }
                        $hours = floor($seconds / 3600);
                        $seconds -= $hours * 3600;
                        $minutes = floor($seconds / 60);
                        $seconds -= $minutes * 60;
                        if ($seconds < 9) {
                            $seconds = "0" . $seconds;
                        }
                        if ($minutes < 9) {
                            $minutes = "0" . $minutes;
                        }
                        if ($hours < 9) {
                            $hours = "0" . $hours + $first_total_hours;
                        }
                        $total_worked_time = "{$hours}:{$minutes}:{$seconds}";
                    } else {
                        $total_bookings = DB::table('bookings')->where('stylist_id', $user_id)->where('meeting_status', 'ended')->get();
                        $total_count_found = count($total_bookings);
                        $time_check = strtotime("00:60:00");
                        $total_worked_time1 = $time_check * count($total_bookings);
                        $total_worked_time = date('H:i:s', $total_worked_time1);
                        $minutes = $total_count_found * 60;
                        $endTime = date("H:i:s", strtotime('+' . $minutes . 'minutes', $time_check));
                        $total_worked_time = "$endTime";
                    }

//                       $total_worked_time += strtotime($re_book->time);
//                   print_r($total_times); die;
                } else {
                    $total_worked_time = '';
                }
                //get stylist working hours END
                //Find total earned by stylist START
                $total_ended = DB::table('bookings')->where('stylist_id', $user_id)->where('meeting_status', 'ended')->count();
                if ($total_ended != '0') {
                    $get_total_bookings = DB::table('bookings')->where('stylist_id', $user_id)->where('meeting_status', 'ended')->get();
                    foreach ($get_total_bookings as $get_total_booking) {
                        $get_payments = DB::table('first_payment')->where('booking_id', $get_total_booking->id)->get();
                        foreach ($get_payments as $get_payment) {
                            $all_payments[] = (array) $get_payment;
                        }
                    }
                    $total_amount_earned = 0;
                    foreach ($all_payments as $all_payment) {
                        $total_amount_earned += $all_payment['payment_amount'];
                    }
                    $stylist_earned = "$total_amount_earned";
                } else {
                    $stylist_earned = '0';
                }
                //Find total earned by stylist END

                $get_user_banners = DB::table('stylist_banners')->where('user_id', $user_id)->orderBy('id', 'desc')->first();
                if (isset($get_user_banners->image1) && !empty($get_user_banners->image1)) {
                    $i1 = url('../storage/stylist_banner') . '/' . $get_user_banners->image1;
                } else {
                    $i1 = '';
                }
                if (isset($get_user_banners->image2) && !empty($get_user_banners->image2)) {
                    $i2 = url('../storage/stylist_banner') . '/' . $get_user_banners->image2;
                } else {
                    $i2 = '';
                }
                if (isset($get_user_banners->image3) && !empty($get_user_banners->image3)) {
                    $i3 = url('../storage/stylist_banner') . '/' . $get_user_banners->image3;
                } else {
                    $i3 = '';
                }
                if (isset($get_user_banners->image4) && !empty($get_user_banners->image4)) {
                    $i4 = url('../storage/stylist_banner') . '/' . $get_user_banners->image4;
                } else {
                    $i4 = '';
                }
                if ($get_user_banners != '') {
                    $all_banners = array($i1, $i2, $i3, $i4);
                } else {
                    $all_banners = [];
                }

                if (isset($get_user_banners->id) && !empty($get_user_banners->id)) {
                    $banner_id = $get_user_banners->id;
                } else {
                    $banner_id = '';
                }

                $resultData = array(
                    'id' => $user_datas->id,
                    'name' => $user_datas->name,
                    'last_name' => $user_datas->last_name,
                    'email' => $user_datas->email,
                    'image' => $image_data,
                    'phone' => $user_datas->phone,
                    'gender' => $user_datas->gender,
                    'status' => $user_datas->avail_status,
                    'type' => $user_datas->type,
                    'view_count' => $user_datas->view_count,
                    'share_count' => $user_datas->share_count,
                    'user_currency' => $user_datas->user_currency,
                    'user_price' => $user_datas->user_price,
                    'user_banners' => $all_banners,
                    'banner_id' => $banner_id,
                    'user_price_type' => $user_datas->user_price_type,
                    'total_worked_time' => $total_worked_time,
                    'total_earned' => $stylist_earned,
                    'fav' => $saved_check,
                    'shopping_locations' => $loc_data,
                    'speciality_data' => $speciality_data,
                    'rating_percentage' => ceil($average),
                    'rating_count' => $count_rating,
                    '3_rating' => $get_rating3,
                    '4_rating' => $get_rating4,
                    '5_rating' => $get_rating5,
                );
                //  }
                return json_encode(array('status' => "200", 'message' => 'User detail', 'result' => $resultData, 'speciality_data' => $speciality_data));
            } else {
                $res['status'] = "201";
                $res['msg'] = "No user exist with this id";

                return json_encode($res);
            }
        } else {
            $res['status'] = "201";
            $res['msg'] = "User id is missing";

            return json_encode($res);
        }
    }

    //Get user detail END
    //Update password START
    public function update_password(Request $request) {
        $new_password = $request->input('new_password');
        $confirm_password = $request->input('confirm_password');
        $user_phone = $request->input('user_phone');
        $user_type = $request->input('user_type');
        if ($new_password == $confirm_password) {
            $pass = Hash::make($new_password);
            DB::table('users')->where('phone', $user_phone)->where('type', $user_type)->update([
                'password' => $pass
            ]);
            $userData = DB::table('users')->where('phone', $user_phone)->where('type', $user_type)->first();
            $googleApiKey = 'AAAA7JZNVfI:APA91bFThKoVHXIiLOEgVL5t9PDIzZFlYZtMU-ow_DtmwvIHBs6xWQfZGxS93ZgKPTX_e9oYnCXLKPXqVX3o82U4fpe1cRF6IzPtjy6fRft_LSHc_xD6H_9RQuL22_1ZANgRTlMbnqYf';
            // print_r($data); die;
            $registrationIds = array($userData->device_token);
            $message = "Congrats! your password has been updated successfully.";
            $title = "Updated password!";
            $msg = array(
                'message' => $message,
                'title' => $title,
                'vibrate' => 1,
                'sound' => 1,
                'type' => '11', //password updated
                'priority' => 'high',
                'content_available' => true,
            );
            $fields = array(
                'registration_ids' => $registrationIds,
                'data' => $msg,
                'notification' => $msg
            );
            $headers = array(
                'Authorization: key=' . $googleApiKey,
                'Content-Type: application/json'
            );
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
            $result = curl_exec($ch);


            curl_close($ch);


            $res['status'] = "200";
            $res['msg'] = "Congrats! your password has been updated";

            return json_encode($res);
        } else {
            $res['status'] = "201";
            $res['msg'] = "Password and confirm password does not match";

            return json_encode($res);
        }
    }

    //Update password END
    //forget password START
    public function forget_password(Request $request) {
        $user_phone = $request->input('user_phone');
        $user_type = $request->input('user_type');
        $user_exist_check = DB::table('users')->where('phone', $user_phone)->where('type', $user_type)->count();
        if ($user_exist_check != '0') {
            $res['status'] = "200";
            $res['msg'] = "true";
            return json_encode($res);
        } else {
            $res['status'] = "201";
            $res['msg'] = "User with this phone number not registered.";
            return json_encode($res);
        }
    }

    //forget password END
    //Update use detail START
    public function update_profile(Request $request) {
        $user_id = $request->input('id');
        $user_count = User::where('id', $user_id)->count();
        $user_data = User::where('id', $user_id)->first();
//        $variable1 = $request->input('email');
//        $variable2 = $request->input('phone');
//        $user_email_phone_exist = User::where('id', '!=', $user_id)
//                        ->where(function($query) use ($variable1, $variable2) {
//                            $query->where('email', '=', $variable1)
//                            ->orWhere('phone', '=', $variable2);
//                        })->count();
        //if ($user_email_phone_exist != '0') {
        if ($user_count != '0') {
            if ($file = $request->hasFile('image')) {
                $file = $request->file('image');
                $imageType = $file->getClientmimeType();
                // print_r($imageType); die;
                $rules = array(
                    'image' => 'mimes:jpeg,jpg,png',
                );

                $validator = Validator::make($request->all(), $rules);

                if ($validator->fails()) {
                    $res['status'] = "201";
                    $res['msg'] = "Image format must be jpeg,jpg,png";
                    return json_encode($res);
                } else {
                    $fileName = $file->getClientOriginalName();
                    $fileNameUnique = time() . '_' . $fileName;
                    $destinationPath = storage_path() . '/files/';
                    $file->move($destinationPath, $fileNameUnique);
                }
                $imageData = $fileNameUnique;
            } else {
                $imageData = $user_data->image;
            }

            User::where('id', $user_id)->update([
                'name' => $request->input('name'),
                'last_name' => $request->input('last_name'),
//                    'phone' => $request->input('phone'),
//                    'email' => $request->input('email'),
                'gender' => $request->input('gender'),
                'name' => $request->input('name'),
                'user_price' => $request->input('user_price'),
                'image' => $imageData,
            ]);
            if ($request->input('shopping_location')) {
                DB::table('shopping_locations')->where('user_id', $user_data->id)->delete();
                $shoping_loc = json_decode($request->input('shopping_location'));
                foreach ($shoping_loc as $shoping_locs) {
                    $childId = DB::table('shopping_locations')->insertGetId([
                        'user_id' => $user_data->id,
                        'lat' => $shoping_locs->lat,
                        'lng' => $shoping_locs->lng,
                        'city' => $shoping_locs->city
                            ]
                    );
                }
            }

            if ($request->input('specialties')) {
                DB::table('user_specialties')->where('user_id', $user_data->id)->delete();
                $specialties = json_decode($request->input('specialties'));
                foreach ($specialties as $specialtie) {
                    $childId = DB::table('user_specialties')->insertGetId([
                        'user_id' => $user_data->id,
                        'cat_id' => $specialtie->cat_id
                            ]
                    );
                }
            }


            $user_data = User::where('id', $user_id)->with('shopping_locations', 'user_speciality')->get();
            //print_r($user_data); die;
            foreach ($user_data as $user_datas) {
                if (isset($user_datas->image) && !empty($user_datas->image)) {
                    $image_data = url('../storage/files/') . '/' . $user_datas->image;
                } else {
                    $image_data = '';
                }

                if (count($user_datas['user_speciality']) == '' || count($user_datas['user_speciality']) == '0') {
                    $speciality_data = array();
                } else {
                    foreach ($user_datas['user_speciality'] as $user_speciality) {
                        $cat_data = Category::where('id', $user_speciality->cat_id)->first();
                        $speciality_data[] = array(
                            'id' => $user_speciality->id,
                            'cat_id' => $user_speciality->cat_id,
                            'cat_name' => $cat_data->title,
                        );
                    }
                }

                if (count($user_datas['shopping_locations']) == '' || count($user_datas['shopping_locations']) == '0') {
                    $loc_data = array();
                } else {
                    foreach ($user_datas['shopping_locations'] as $shoping_loc) {
                        $loc_data[] = array(
                            'id' => $shoping_loc->id,
                            'lat' => $shoping_loc->lat,
                            'lng' => $shoping_loc->lng,
                            'city' => $shoping_loc->city,
                        );
                    }
                }


                $resultData[] = array(
                    'id' => $user_datas->id,
                    'name' => $user_datas->name,
                    'last_name' => $user_datas->last_name,
                    'email' => $user_datas->email,
                    'image' => $image_data,
                    'phone' => $user_datas->phone,
                    'gender' => $user_datas->gender,
                    'status' => $user_datas->avail_status,
                    'type' => $user_datas->type,
                    'view_count' => $user_datas->view_count,
                    'share_count' => $user_datas->share_count,
                    'shopping_locations' => $loc_data,
                    'speciality_data' => $speciality_data
                );
            }
            return json_encode(array('status' => "200", 'message' => 'User detail', 'result' => $resultData));

            // return json_encode($res);
        } else {
            $res['status'] = "201";
            $res['msg'] = "User with this id does not exist";

            return json_encode($res);
        }
        //} 
    }

    //Update use detail END
    //update user Status START
    public function user_status(Request $request) {
        $user_id = $request->input('id');
        $avail_status = $request->input('avail_status');
        $user_count = User::where('id', $user_id)->count();
        if ($avail_status == 'offline' || $avail_status == 'online') {
            if ($user_count != '0') {
                User::where('id', $user_id)->update(['avail_status' => $avail_status]);
                $res['status'] = "200";
                $res['msg'] = "User status has been updated successfully";

                return json_encode($res);
            } else {
                $res['status'] = "201";
                $res['msg'] = "User with this id does not exist";

                return json_encode($res);
            }
        } else {
            $res['status'] = "201";
            $res['msg'] = "avail status text must be online or offline";

            return json_encode($res);
        }
    }

    //update user Status END
    //Get term and conditions START
    public function term_conditions(Request $request) {
        $terms = Term::get();
        $terms_count = Term::count();
        if ($terms_count == '0') {
            $res['status'] = "201";
            $res['msg'] = "No term and conditions for now";

            return json_encode($res);
        } else {
            foreach ($terms as $term) {
                $result[] = array(
                    'id' => $term->id,
                    'title' => $term->title,
                    'description' => $term->description,
                );
            }
            return json_encode(array('status' => "200", 'message' => 'all term and conditions', 'result' => $result));
        }
    }

    //Get term and conditions END
    //Get Explore page Data START
    public function explore(Request $request) {
        //Get all category with active status
        $all_cat = Category::where('status', '1')->get();
        $all_cat_count = Category::where('status', '1')->count();
        if ($all_cat_count == '0') {
            $all_cat_data = [];
        } else {
            foreach ($all_cat as $all_cats) {
                if (isset($all_cats->image) && !empty($all_cats->image)) {
                    $image_data = url('../storage/category_files/') . '/' . $all_cats->image;
                } else {
                    $image_data = '';
                }
                $all_cat_data[] = array(
                    'id' => $all_cats->id,
                    'title' => $all_cats->title,
                    'image' => $image_data,
                );
            }
        }

        //Get all popular stylist START
        $all_users = User::orderBy('share_count', 'desc')->where('type', 'stylist')->with('shopping_locations', 'user_speciality')->where('id', '!=', '2')->where('avail_status', 'online')->where('id', '!=', $request->input('user_id'))->take(10)->get();
        $all_users_count = User::orderBy('share_count', 'desc')->where('type', 'stylist')->with('shopping_locations', 'user_speciality')->where('id', '!=', '2')->where('avail_status', 'online')->where('id', '!=', $request->input('user_id'))->count();
        if ($all_users_count == '0') {
            $all_users_data = [];
        } else {
//            print_r($all_users_count); die;
            foreach ($all_users as $all_user) {
                if (isset($all_user->image) && !empty($all_user->image)) {
                    $image_data = url('../storage/files/') . '/' . $all_user->image;
                } else {
                    $image_data = '';
                }

                if (count($all_user['shopping_locations']) == '' || count($all_user['shopping_locations']) == '0') {
                    $loc_data = array();
                } else {
//                    foreach ($all_user['shopping_locations'] as $shoping_loc) {
                    $loc_data[] = array(
                        'id' => $all_user['shopping_locations'][0]->id,
                        'lat' => $all_user['shopping_locations'][0]->lat,
                        'lng' => $all_user['shopping_locations'][0]->lng,
                        'city' => $all_user['shopping_locations'][0]->city,
                    );
//                    }
                }
                //Get rating of specialist START
                $get_rating_data = DB::table('ratings')->select('rate')->where('stylist_id', $all_user->id)->get();
                $get_data_rate_total = 0;
                foreach ($get_rating_data as $get_rating_datas) {
                    $get_data_rate_total += $get_rating_datas->rate;
                }

                if (count($get_rating_data) != '0') {
                    $average = $get_data_rate_total / count($get_rating_data);
                    $count_rating = count($get_rating_data);
                } else {
                    $average = '0';
                    $count_rating = '0';
                }
                //Get rating of specialist END

                $all_users_data[] = array(
                    'id' => $all_user->id,
                    'first_name' => $all_user->name,
                    'last_name' => $all_user->last_name,
                    'email' => $all_user->email,
                    'image' => $image_data,
                    'image' => $image_data,
                    'image' => $image_data,
                    'user_location' => $loc_data,
                    'rating_percentage' => ceil($average),
                    'rating_count' => $count_rating,
                );
            }
        }
        $latitude = $request->input('lat');
        $longitude = $request->input('lng');
        $user_id = $request->input('user_id');
        $near_stylists = ShoppingLocation::select(DB::raw('*, ( 6367 * acos( cos( radians(' . $latitude . ') ) * cos( radians( lat ) ) * cos( radians( lng ) - radians(' . $longitude . ') ) + sin( radians(' . $latitude . ') ) * sin( radians( lat ) ) ) ) AS distance'))
                ->having('distance', '<', 25)
                ->where('user_id', '!=', $user_id)
                ->orderBy('distance')
                ->take(10)
                ->get();
        if (count($near_stylists) == '0') {
            $all_stylist_data = [];
        } else {
            foreach ($near_stylists as $near_stylist) {
                $all_users = User::orderBy('share_count', 'desc')->where('type', 'stylist')->where('id', $near_stylist->user_id)->where('avail_status', 'online')->take(10)->get();
                foreach ($all_users as $all_user) {
                    if (isset($all_user->image) && !empty($all_user->image)) {
                        $image_data = url('../storage/files/') . '/' . $all_user->image;
                    } else {
                        $image_data = '';
                    }

                    $shoping_loc_count = ShoppingLocation::where('lat', $near_stylist->lat)->where('user_id', '!=', $user_id)->where('lng', $near_stylist->lng)->count();
                    $shoping_loc_get = ShoppingLocation::where('lat', $near_stylist->lat)->where('user_id', '!=', $user_id)->where('lng', $near_stylist->lng)->get();
                    if ($shoping_loc_count == '0') {
                        $loc_data1 = array();
                    } else {
//                        foreach ($shoping_loc_get as $shoping_loc) {
                        $loc_data1[] = array(
                            'id' => $shoping_loc_get[0]->id,
                            'lat' => $shoping_loc_get[0]->lat,
                            'lng' => $shoping_loc_get[0]->lng,
                            'city' => $shoping_loc_get[0]->city,
                        );
//                        }
                    }

                    //Get rating of specialist START
                    $get_rating_data = DB::table('ratings')->select('rate')->where('stylist_id', $all_user->id)->get();
                    $get_data_rate_total = 0;
                    foreach ($get_rating_data as $get_rating_datas) {
                        $get_data_rate_total += $get_rating_datas->rate;
                    }

                    if (count($get_rating_data) != '0') {
                        $average = $get_data_rate_total / count($get_rating_data);
                        $count_rating = count($get_rating_data);
                    } else {
                        $average = '0';
                        $count_rating = '0';
                    }
                    //Get rating of specialist END

                    $loc_data12 = array_unique($loc_data1, SORT_REGULAR);
                    //picked up END
                    $all_stylist_data[] = array(
                        'id' => $all_user->id,
                        'first_name' => $all_user->name,
                        'last_name' => $all_user->last_name,
                        'email' => $all_user->email,
                        'image' => $image_data,
                        'user_location' => $loc_data1,
                        'rating_percentage' => ceil($average),
                        'rating_count' => $count_rating,
                    );
                }
            }
        }
        //Get all popular stylist END
        //picked up START
        $picked_up_user_data = UserSpecialty::where('user_id', $user_id)->get();
//        print_r($picked_up_user_data);
//        die;
        if (count($picked_up_user_data) != '0') {
            foreach ($picked_up_user_data as $picked_up_user_datas) {
                $pick = DB::table('user_specialties')
                        ->leftjoin('users', 'users.id', '=', 'user_specialties.user_id')
                        ->select('user_specialties.*')
                        ->where('user_specialties.user_id', '!=', $user_id)
                        ->where('user_specialties.cat_id', $picked_up_user_datas->cat_id)
                        ->where('users.type', 'stylist')
                        ->where('users.avail_status', 'online')
                        ->first();
//                $pick = DB::table('user_specialties')->where('cat_id', $picked_up_user_datas->cat_id)->where('user_id', '!=', $user_id)->first();
                $pick_count = DB::table('user_specialties')->where('cat_id', $picked_up_user_datas->cat_id)->where('user_id', '!=', $user_id)->count();


                if ($pick_count == 0) {
                    continue;
                }
//                print_r($pick);
//                die;
                if (isset($pick)) {
                    if (property_exists($pick, 'user_id') || !empty($pick)) {

                        $all_users = DB::table('users')->where('id', $pick->user_id)->where('avail_status', 'online')->where('type', 'stylist')->get();
                        foreach ($all_users as $all_user) {
                            if (isset($all_user->image) && !empty($all_user->image)) {
                                $image_data = url('../storage/files/') . '/' . $all_user->image;
                            } else {
                                $image_data = '';
                            }

                            $shoping_loc_count = ShoppingLocation::where('user_id', $all_user->id)->count();
                            $shoping_loc_get = ShoppingLocation::where('user_id', $all_user->id)->get();
                            if ($shoping_loc_count == '0') {
                                $loc_data2 = array();
                            } else {
//                            foreach ($shoping_loc_get as $shoping_loc) {
                                $loc_data2[] = array(
                                    'id' => $shoping_loc_get[0]->id,
                                    'lat' => $shoping_loc_get[0]->lat,
                                    'lng' => $shoping_loc_get[0]->lng,
                                    'city' => $shoping_loc_get[0]->city,
                                );
//                            }
                            }
                            //Get rating of specialist START
                            $get_rating_data = DB::table('ratings')->select('rate')->where('stylist_id', $all_user->id)->get();
                            $get_data_rate_total = 0;
                            foreach ($get_rating_data as $get_rating_datas) {
                                $get_data_rate_total += $get_rating_datas->rate;
                            }

                            if (count($get_rating_data) != '0') {
                                $average = $get_data_rate_total / count($get_rating_data);
                                $count_rating = count($get_rating_data);
                            } else {
                                $average = '0';
                                $count_rating = '0';
                            }
                            //Get rating of specialist END
                           $loc_data23 = array_unique($loc_data2, SORT_REGULAR);
                           $loc_data234 = array_values($loc_data23);
                            $all_pick_data[] = array(
                                'id' => $all_user->id,
                                'first_name' => $all_user->name,
                                'last_name' => $all_user->last_name,
                                'email' => $all_user->email,
                                'image' => $image_data,
                                'image' => $image_data,
                                'image' => $image_data,
                                'user_location' => $loc_data234,
                                'rating_percentage' => ceil($average),
                                'rating_count' => $count_rating,
                            );
                        }
                    } else {
                        $all_pick_data = [];
                    }
                } else {
                    $all_pick_data = [];
                    $all_stylist_data = [];
                }


//                    print_r($pick); 
            }
            //die;
            $all_pick_datas1 = array_unique($all_pick_data, SORT_REGULAR);
            $all_pick_datas = array_values($all_pick_datas1);
        } else {
            $all_pick_datas = [];
        }

        //picked up END

        return json_encode(array('status' => "200", 'message' => 'Explore screen data', 'categories' => $all_cat_data, 'popular_stylist' => $all_users_data, 'near_stylist' => $all_stylist_data, 'picked_for_you' => $all_pick_datas));
    }

    //Get Explore page Data END
    //giving rating to stylist START
    public function rating(Request $request) {
        $user_id = $request->input('user_id');
        $stylist_id = $request->input('stylist_id');
        $rate = $request->input('rate');
        $rate_description = $request->input('rate_description');
        $booking_id = $request->input('booking_id');
        $to_whom = $request->input('to_whom');
        $rate_description = $request->input('rate_description');
        if ($to_whom == 'to client') {
            $check = DB::table('rating_to_user')->where('user_id', $user_id)->where('stylist_id', $stylist_id)->where('booking_id', $booking_id)->count();
            if ($check != '0') {
                $res['status'] = "201";
                $res['msg'] = "You have already given rating to this Booking";

                return json_encode($res);
            } else {
                DB::table('rating_to_user')->insert([
                    'user_id' => $user_id,
                    'stylist_id' => $stylist_id,
                    'rate' => $rate,
                    'rate_description' => $rate_description,
                    'booking_id' => $booking_id,
                    'to_whom' => $to_whom,
                ]);
                $res['status'] = "200";
                $res['msg'] = "Thanks for rating this booking";

                return json_encode($res);
            }
        } else {
            $check = DB::table('ratings')->where('user_id', $user_id)->where('stylist_id', $stylist_id)->where('booking_id', $booking_id)->count();
            if ($check != '0') {
                $res['status'] = "201";
                $res['msg'] = "You have already given rating to this Booking";

                return json_encode($res);
            } else {
                DB::table('ratings')->insert([
                    'user_id' => $user_id,
                    'stylist_id' => $stylist_id,
                    'rate' => $rate,
                    'rate_description' => $rate_description,
                    'booking_id' => $booking_id,
                    'to_whom' => $to_whom,
                ]);
                $res['status'] = "200";
                $res['msg'] = "Thanks for rating this booking";

                return json_encode($res);
            }
        }
    }

    //giving rating to stylist END
    //search suggestions START
    public function search_suggestions(Request $request) {
        $user_id = $request->input('user_id');
        //print_r($user_id); die; 
        //Top popular search START
        $top_cats = DB::table('categories')->where('status', '1')->orderBy('view_count', 'desc')->take(2)->get()->toArray();
        $top_user = DB::table('users')->where('id', '!=', $user_id)->where('status', '1')->orderBy('view_count', 'desc')->take(1)->get()->toArray();
        $final_top_search_array = array_merge($top_cats, $top_user);
        //  print_r($final_top_search_array); die;
        //Top popular search END   
        foreach ($final_top_search_array as $final_top_search_arrays) {
            $id = $final_top_search_arrays->id;
            if (isset($final_top_search_arrays->title) and ! empty($final_top_search_arrays->title)) {
                $title1 = $final_top_search_arrays->title;
                $cat_style1 = 'style';
            } else {
                $cat_title1 = '';
            }
            if (isset($final_top_search_arrays->name) and ! empty($final_top_search_arrays->name)) {
                $title1 = $final_top_search_arrays->name . ' ' . $final_top_search_arrays->last_name;
                $cat_style1 = 'stylist';
            } else {
                $stylist_name1 = '';
            }
            $pop_search_complete[] = array(
                'popular_id' => $id,
                'title' => $title1,
                'popular_type' => $cat_style1,
            );
        }

        //Top recently viewed search START
        $user_category_searches = DB::table('user_category_searches')->where('user_id', $user_id)->orderBy('id', 'desc')->take(2)->get()->toArray();
        $user_stylist_searches = DB::table('user_stylist_searches')->where('user_id', $user_id)->orderBy('id', 'desc')->take(2)->get()->toArray();
        $user_text_searches = DB::table('user_text_searches')->where('user_id', $user_id)->orderBy('id', 'desc')->take(5)->get()->toArray();
        $final_top_search_array1 = array_merge($user_text_searches, $user_category_searches, $user_stylist_searches);
        //print_r($user_category_searches); die;

        foreach ($final_top_search_array1 as $final_top_search_array12) {
            $final_top_search_array123[] = (object) $final_top_search_array12;
        }
        if (isset($final_top_search_array123) && !empty($final_top_search_array123)) {
            //print_r($final_top_search_array123); die;
            foreach ($final_top_search_array123 as $final_top_search_array1s) {
                $id1 = $final_top_search_array1s->id;

                if (isset($final_top_search_array1s->cat_id) and ! empty($final_top_search_array1s->cat_id)) {
                    $cat_id = $final_top_search_array1s->cat_id;
                    $cat_data = DB::table('categories')->where('id', $cat_id)->first();
                    //print_r($cat_data);
                    $actual_id = $cat_data->id;
                    $cat_title = $cat_data->title;
                    $cat_style = 'style';
                } elseif (isset($final_top_search_array1s->stylist_id) and ! empty($final_top_search_array1s->stylist_id)) {
                    $user_id1 = $final_top_search_array1s->stylist_id;

                    $user_data = DB::table('users')->where('id', $user_id1)->first();
                    $actual_id = $user_data->id;
                    $cat_title = $user_data->name . ' ' . $user_data->last_name;
                    $cat_style = 'stylist';
                } else {
                    $cat_title = $final_top_search_array1s->title;
                    $cat_style = '';
                    $actual_id = '';
                }

                $pop_search_complete1[] = array(
                    'recent_id' => $id1,
                    'title' => $cat_title,
                    'actual_id' => $actual_id,
                    'recent_type' => $cat_style,
                );
                //  print_r($pop_search_complete1);
            }
            // die;
        } else {
            $pop_search_complete1 = [];
        }
        $category_data = Category::where('status', '1')->orderBy('view_count', 'desc')->take(10)->get();
        foreach ($category_data as $category_datas) {
            $style_data[] = array(
                'id' => $category_datas->id,
                'title' => $category_datas->title,
                'type' => 'style'
            );
        }

        $final_data = array('popular_search' => $pop_search_complete, 'recent_search' => $pop_search_complete1);

        //Top recently viewed search END
        //Style search START
        //Style search END
        //stylist search START
        //print_r($user_id); die;
        $user_data = User::where('status', '1')->where('id', '!=', $user_id)->orderBy('view_count', 'desc')->where('type', 'stylist')->take(10)->get();
        foreach ($user_data as $user_datas) {
            $stylist_data[] = array(
                'id' => $user_datas->id,
                'title' => $user_datas->name . ' ' . $user_datas->last_name,
                'type' => 'stylist'
            );
        }

        //stylist search END
        return json_encode(array('status' => "200", 'message' => 'search suggestions data', 'top' => $final_data, 'style' => $style_data, 'stylist' => $stylist_data));
    }

    //search suggestions END
    //search data step1 START
    public function search_step1(Request $request) {
        $user_id = $request->input('user_id');
        $search_data = $request->input('search_data');
        $user_count = User::where('id', $user_id)->where('status', '1')->count();
        $search_count = DB::table('user_text_searches')->where('user_id', $user_id)->where('title', $search_data)->count();
        if ($search_count == '0') {
            DB::table('user_text_searches')->insert([
                'user_id' => $user_id,
                'title' => $search_data
            ]);
        }

        if ($user_count != '0') {
            //echo $user_id; die;
            $user_detail = User::where('id', $user_id)->where('id', '!=', $user_id)->where('status', '1')->first();
            $user_search_detail = User::where('status', '1')->where('id', '!=', $user_id)->where('name', 'LIKE', '%' . $search_data . '%')->take(15)->get();

            $category_search_detail = Category::where('title', 'LIKE', '%' . $search_data . '%')->where('status', '1')->take(15)->get();

            if (count($category_search_detail) != '0') {
                foreach ($category_search_detail as $category_datas) {
                    $cat_data[] = array(
                        'id' => $category_datas->id,
                        'title' => $category_datas->title,
                        'type' => 'style'
                    );
                }
            } else {
                $cat_data = [];
            }

            if (count($user_search_detail) != '0') {
                foreach ($user_search_detail as $user_datas) {
                    $users_data[] = array(
                        'id' => $user_datas->id,
                        'title' => $user_datas->name . ' ' . $user_datas->last_name,
                        'type' => 'stylist'
                    );
                }
            } else {
                $users_data = [];
            }


            return json_encode(array('status' => "200", 'message' => 'search suggestions data', 'stylists' => $users_data, 'categories' => $cat_data));
        } else {
            $res['status'] = "201";
            $res['msg'] = "No user exist with this user id";

            return json_encode($res);
        }
    }

    //search data step1 END
    //search final Step START
    public function search_final_step(Request $request) {
        $user_id = $request->input('user_id');
        $search_data = $request->input('search_data');
        $latitude = $request->input('lat');
        $longitude = $request->input('lng');
        $user_data = User::where('id', $user_id)->first();
        $data = json_decode($search_data);
//        print_r($data);
//        die;
        foreach ($data as $datas) {
            //for stylist
            if ($datas->type == 'stylist') {
                $check_count = DB::Table('user_stylist_searches')->where('stylist_id', $datas->id)->where('user_id', $user_id)->count();
                if ($check_count == '0') {
                    DB::table('user_stylist_searches')->insert([
                        'user_id' => $user_id,
                        'stylist_id' => $datas->id,
                    ]);
                }
//                $near_stylists = ShoppingLocation::select(DB::raw('*, ( 6367 * acos( cos( radians(' . $latitude . ') ) * cos( radians( lat ) ) * cos( radians( lng ) - radians(' . $longitude . ') ) + sin( radians(' . $latitude . ') ) * sin( radians( lat ) ) ) ) AS distance'))
//                ->having('distance', '<', 25)
//                ->where('user_id', '!=', $datas->id)
//                ->orderBy('distance')
//                ->first();
                $all_user = User::where('id', $datas->id)->first();
                if (isset($all_user->image) && !empty($all_user->image)) {
                    $image_data = url('../storage/files/') . '/' . $all_user->image;
                } else {
                    $image_data = '';
                }

                $shoping_loc_count = ShoppingLocation::where('user_id', $all_user->id)->count();
//                  $shoping_loc_count = ShoppingLocation::select(DB::raw('*, ( 6367 * acos( cos( radians(' . $latitude . ') ) * cos( radians( lat ) ) * cos( radians( lng ) - radians(' . $longitude . ') ) + sin( radians(' . $latitude . ') ) * sin( radians( lat ) ) ) ) AS distance'))
//                ->having('distance', '<', 25)
//                ->where('user_id',  $all_user->id)
//                ->orderBy('distance')
//                ->count();
//                $shoping_loc_get = ShoppingLocation::where('user_id', $all_user->id)->get();
                $shoping_loc_get = ShoppingLocation::select(DB::raw('*, ( 6367 * acos( cos( radians(' . $latitude . ') ) * cos( radians( lat ) ) * cos( radians( lng ) - radians(' . $longitude . ') ) + sin( radians(' . $latitude . ') ) * sin( radians( lat ) ) ) ) AS distance'))
                        ->having('distance', '<', 25)
                        ->where('user_id', $all_user->id)
                        ->orderBy('distance')
                        ->get();
                //print_r($shoping_loc_get); die;
                if ($shoping_loc_count == '0') {
                    $loc_data2 = array();
                } else {
                    $loc_data2 = [];
                    foreach ($shoping_loc_get as $shoping_loc) {
                        $loc_data2[] = array(
                            'id' => $shoping_loc->id,
                            'lat' => $shoping_loc->lat,
                            'lng' => $shoping_loc->lng,
                            'city' => $shoping_loc->city,
                            'distance' => round($shoping_loc->distance) . ' km',
                        );
                    }
                }
                //Get rating of specialist START
                $get_rating_data = DB::table('ratings')->select('rate')->where('stylist_id', $all_user->id)->get();
                $get_data_rate_total = 0;
                foreach ($get_rating_data as $get_rating_datas) {
                    $get_data_rate_total += $get_rating_datas->rate;
                }

                if (count($get_rating_data) != '0') {
                    $average = $get_data_rate_total / count($get_rating_data);
                    $count_rating = count($get_rating_data);
                } else {
                    $average = '0';
                    $count_rating = '0';
                }
                //Get rating of specialist END

                $saved_count_check = DB::table('saved_stylist')->where('user_id', $request->input('user_id'))->where('stylist_id', $all_user->id)->where('status', '1')->first();
                //print_r($saved_count_check)); die;
                if (isset($saved_count_check) && !empty($saved_count_check)) {
                    $saved_check = $saved_count_check->status;
                } else {
                    $saved_check = '0';
                }

                $all_pick_data[] = array(
                    'id' => $all_user->id,
                    'first_name' => $all_user->name,
                    'last_name' => $all_user->last_name,
                    'email' => $all_user->email,
                    'fav' => $saved_check,
                    'image' => $image_data,
                    'image' => $image_data,
                    'image' => $image_data,
                    'user_location' => array_unique($loc_data2, SORT_REGULAR),
                    'rating_percentage' => ceil($average),
                    'rating_count' => $count_rating,
                );


                $all_pick_datas = array_unique($all_pick_data, SORT_REGULAR);
            }

            //for style
            if ($datas->type == 'style') {
                $check_count = DB::Table('user_category_searches')->where('cat_id', $datas->id)->where('user_id', $user_id)->count();
                if ($check_count == '0') {
                    DB::table('user_category_searches')->insert([
                        'user_id' => $user_id,
                        'cat_id' => $datas->id,
                    ]);
                }

                $picks = DB::table('user_specialties')->where('cat_id', $datas->id)->get();
                $pick_count = DB::table('user_specialties')->where('cat_id', $datas->id)->count();


                if ($pick_count == 0) {
                    continue;
                }
                // print_r(count($picks)); die;
                if (count($picks) != '0') {
                    foreach ($picks as $pick) {
                        $all_users = DB::table('users')->where('id', $pick->user_id)->where('type', 'stylist')->get();
                        foreach ($all_users as $all_user) {
                            if (isset($all_user->image) && !empty($all_user->image)) {
                                $image_data = url('../storage/files/') . '/' . $all_user->image;
                            } else {
                                $image_data = '';
                            }

                            $shoping_loc_count = ShoppingLocation::where('user_id', $all_user->id)->count();
//                            $shoping_loc_count = ShoppingLocation::select(DB::raw('*, ( 6367 * acos( cos( radians(' . $latitude . ') ) * cos( radians( lat ) ) * cos( radians( lng ) - radians(' . $longitude . ') ) + sin( radians(' . $latitude . ') ) * sin( radians( lat ) ) ) ) AS distance'))
//                ->having('distance', '<', 25)
//                ->where('user_id',  $all_user->id)
//                ->orderBy('distance')
//                ->count();
//                            $shoping_loc_get = ShoppingLocation::where('user_id', $all_user->id)->get();

                            $shoping_loc_get = ShoppingLocation::select(DB::raw('*, ( 6367 * acos( cos( radians(' . $latitude . ') ) * cos( radians( lat ) ) * cos( radians( lng ) - radians(' . $longitude . ') ) + sin( radians(' . $latitude . ') ) * sin( radians( lat ) ) ) ) AS distance'))
                                    ->having('distance', '<', 25)
                                    ->where('user_id', $all_user->id)
                                    ->orderBy('distance')
                                    ->get();

                            if ($shoping_loc_count == '0') {
                                $loc_data2 = array();
                            } else {
                                $loc_data2 = [];
                                foreach ($shoping_loc_get as $shoping_loc) {
                                    $loc_data2[] = array(
                                        'id' => $shoping_loc->id,
                                        'lat' => $shoping_loc->lat,
                                        'lng' => $shoping_loc->lng,
                                        'city' => $shoping_loc->city,
                                        'distance' => round($shoping_loc->distance) . ' km',
                                    );
                                }
                            }
                            //Get rating of specialist START
                            $get_rating_data = DB::table('ratings')->select('rate')->where('stylist_id', $all_user->id)->get();
                            $get_data_rate_total = 0;
                            foreach ($get_rating_data as $get_rating_datas) {
                                $get_data_rate_total += $get_rating_datas->rate;
                            }

                            if (count($get_rating_data) != '0') {
                                $average = $get_data_rate_total / count($get_rating_data);
                                $count_rating = count($get_rating_data);
                            } else {
                                $average = '0';
                                $count_rating = '0';
                            }
                            //Get rating of specialist END
                            $loc321 = array_unique($loc_data2, SORT_REGULAR);
                            $saved_count_check = DB::table('saved_stylist')->where('user_id', $request->input('user_id'))->where('stylist_id', $all_user->id)->where('status', '1')->first();
                            //print_r($saved_count_check)); die;
                            if (isset($saved_count_check) && !empty($saved_count_check)) {
                                $saved_check = $saved_count_check->status;
                            } else {
                                $saved_check = '0';
                            }
                            $all_pick_data[] = array(
                                'id' => $all_user->id,
                                'first_name' => $all_user->name,
                                'last_name' => $all_user->last_name,
                                'email' => $all_user->email,
                                'fav' => $saved_check,
                                'image' => $image_data,
                                'image' => $image_data,
                                'image' => $image_data,
                                'user_location' => $loc321,
                                'rating_percentage' => ceil($average),
                                'rating_count' => $count_rating,
                            );
                        }
                    }
                    // print_r($all_pick_data); die;
                    $all_pick_datas1 = array_unique($all_pick_data, SORT_REGULAR);
                    $all_pick_datas = array_values($all_pick_datas1);

                    // print_r($all_pick_datas1); die;
                } else {

                    $all_pick_datas = [];
                }

//                if (count($picks) != '0') {
//                    
//                } else {
//                    $all_pick_datas = [];
//                }
            }
        }
        //print_r($all_pick_datas); die;
        if (isset($all_pick_datas)) {
            $result_data = $all_pick_datas;
        } else {
            $result_data = [];
        }

        return json_encode(array('status' => "200", 'message' => 'search data', 'result' => $result_data));
    }

    //search final Step END
    //Get category stylist data START
    public function category_data(Request $request) {
        $cat_id = $request->input('cat_id');
//        $cat_data_count = DB::table('user_specialties')->where('cat_id', $cat_id)->count();
//        $cat_data = DB::table('user_specialties')->where('cat_id', $cat_id)->orderBy('id', 'desc')->get();
        $cat_data = DB::table('user_specialties')
                ->leftjoin('users', 'users.id', '=', 'user_specialties.user_id')
                ->select('user_specialties.*')
                ->where('user_specialties.cat_id', '=', $cat_id)
                ->where('users.type', '=', 'stylist')
                ->get();
        $cat_data_count = DB::table('user_specialties')
                ->leftjoin('users', 'users.id', '=', 'user_specialties.user_id')
                ->select('user_specialties.*')
                ->where('user_specialties.cat_id', '=', $cat_id)
                ->where('users.type', '=', 'stylist')
                ->count();

        if ($cat_data_count == '0') {
            $res['status'] = "201";
            $res['msg'] = "No stylist exist for this category";

            return json_encode($res);
        } else {
//            print_r($cat_data); die;
            foreach ($cat_data as $cat_datas) {
                $user_datas = User::where('id', $cat_datas->user_id)->where('type', 'stylist')->orderBy('id', 'desc')->first();
//                print_r($user_datas); die;
                if (isset($user_datas->image) && !empty($user_datas->image)) {
                    $image_data = url('../storage/files/') . '/' . $user_datas->image;
                } else {
                    $image_data = '';
                }

                if (!isset($user_datas['user_speciality'])) {
                    $speciality_data = array();
                } else {
                    foreach ($user_datas['user_speciality'] as $user_speciality) {
                        $cat_data = Category::where('id', $user_speciality->cat_id)->first();
                        $speciality_data[] = array(
                            'id' => $user_speciality->id,
                            'cat_name' => $cat_data->title,
                        );
                    }
                }

                if (!isset($user_datas['shopping_locations'])) {
                    $loc_data = array();
                } else {
                    foreach ($user_datas['shopping_locations'] as $shoping_loc) {
                        $loc_data[] = array(
                            'id' => $shoping_loc->id,
                            'lat' => $shoping_loc->lat,
                            'lng' => $shoping_loc->lng,
                            'city' => $shoping_loc->city,
                        );
                    }
                }

                $get_rating_data = DB::table('ratings')->select('rate')->where('stylist_id', $user_datas->id)->get();
                if (isset($get_rating_data) && !empty($get_rating_data)) {
                    $get_data_rate_total = 0;
                    foreach ($get_rating_data as $get_rating_datas) {
                        $get_data_rate_total += $get_rating_datas->rate;
                    }

                    if (count($get_rating_data) != '0') {
                        $average = $get_data_rate_total / count($get_rating_data);
                        $count_rating = count($get_rating_data);
                    } else {
                        $average = '0';
                        $count_rating = '0';
                    }
                    $get_rating3 = DB::table('ratings')->where('rate', '3')->where('stylist_id', $user_datas->id)->count();
                    $get_rating4 = DB::table('ratings')->where('rate', '4')->where('stylist_id', $user_datas->id)->count();
                    $get_rating5 = DB::table('ratings')->where('rate', '5')->where('stylist_id', $user_datas->id)->count();
                } else {
                    $average = '0';
                    $count_rating = '0';
                    $get_rating3 = '0';
                    $get_rating4 = '0';
                    $get_rating5 = '0';
                }


                $resultData[] = array(
                    'id' => $user_datas->id,
                    'name' => $user_datas->name,
                    'last_name' => $user_datas->last_name,
                    'email' => $user_datas->email,
                    'image' => $image_data,
                    'phone' => $user_datas->phone,
                    'gender' => $user_datas->gender,
                    'status' => $user_datas->avail_status,
                    'type' => $user_datas->type,
                    'view_count' => $user_datas->view_count,
                    'share_count' => $user_datas->share_count,
                    'user_currency' => $user_datas->user_currency,
                    'user_price' => $user_datas->user_price,
                    'user_price_type' => $user_datas->user_price_type,
                    'shopping_locations' => $loc_data,
                    'speciality_data' => $speciality_data,
                    'rating_percentage' => ceil($average),
                    'rating_count' => $count_rating,
                    '3_rating' => $get_rating3,
                    '4_rating' => $get_rating4,
                    '5_rating' => $get_rating5,
                );
                //  }
            }
            return json_encode(array('status' => "200", 'message' => 'Users detail', 'result' => $resultData));
        }
    }

    //Get category stylist data END
    //Book stylist START
    public function book_stylist(Request $request) {
        $user_id = $request->input('user_id');
        $stylist_id = $request->input('stylist_id');
        $user_name = $request->input('user_name');
        $user_email = $request->input('user_email');
        $user_phone = $request->input('user_phone');
        $booking_title = $request->input('booking_title');
        $booking_date = $request->input('booking_date');
        $booking_time = $request->input('booking_time');
        $booking_location = $request->input('booking_location');
        $location_lat = $request->input('location_lat');
        $location_long = $request->input('location_long');
        $user_currency = $request->input('user_currency');
        $user_budget = $request->input('user_budget');
        $user_budget_min = $request->input('user_budget_min');
        $admin_fees=DB::table('admin_fees')->first();
        $booking_count = DB::table('bookings')->where('user_id', $user_id)->where('stylist_id', $stylist_id)->where('booking_date', $booking_date)->count();
        $stylist_payment_detail = DB::table('users')->where('id', $stylist_id)->first();

        if ($booking_count == '0') {
            $last_booking_id = DB::table('bookings')->insertGetId([
                'user_id' => $user_id,
                'stylist_id' => $stylist_id,
                'stylist_price' => $stylist_payment_detail->user_price,
                'stylist_price_type' => $stylist_payment_detail->user_price_type,
                'stylist_currency' => $stylist_payment_detail->user_currency,
                'user_name' => $user_name,
                'user_email' => $user_email,
                'user_phone' => $user_phone,
                'booking_title' => $booking_title,
                'booking_date' => $booking_date,
                'booking_time' => $booking_time,
                'booking_location' => $booking_location,
                'location_lat' => $location_lat,
                'location_long' => $location_long,
                'user_currency' => $user_currency,
                'admin_fees' => $admin_fees->amount,
                'user_budget' => $user_budget,
                'user_budget_min' => $user_budget_min,
                'booking_status' => '0',
                'status' => '1',
            ]);

            $booking_detail = DB::table('bookings')->where('id', $last_booking_id)->get();
            foreach ($booking_detail as $booking_details) {
                $result12[] = array(
                    'booking_id' => $last_booking_id,
                    'stylist_id' => $booking_details->stylist_id,
                    'user_id' => $booking_details->user_id,
                    'user_name' => $booking_details->user_name,
                    'user_email' => $booking_details->user_email,
                    'user_phone' => $booking_details->user_phone,
                    'booking_title' => $booking_details->booking_title,
                    'booking_date' => $booking_details->booking_date,
                    'booking_budget' => $booking_details->user_currency . ' ' . $booking_details->user_budget,
                );
            }

            $time = strtotime($booking_detail[0]->booking_date);
            $formated_date = date('d,M, Y', $time);

            $stylist_detailG = DB::table('users')->where('id', $stylist_id)->first();
            $user_detailG = DB::table('users')->where('id', $user_id)->first();
            $notifiation_data = array(
                "body" => "Your have received a new order.",
                "title" => "New Order!",
                "content_available" => true,
                "priority" => "high"
            );
            //
            $data = array(
                "user_id" => $booking_detail[0]->user_id,
                "stylist_name" => $booking_detail[0]->user_name,
                "user_name" => $user_detailG->name . ' ' . $user_detailG->last_name,
                "booking_id" => $booking_detail[0]->id,
                "stylist_id" => $booking_detail[0]->stylist_id,
            );

            DB::table('bookings')->where('id', $booking_detail[0]->id)->update([
                'default_message' => '<p>Keyword/Occasion:' . $booking_detail[0]->booking_title . '</p><p>Date:' . $formated_date . '</p><p>Budget:' . $booking_detail[0]->user_budget . ' ' . $booking_detail[0]->user_currency . '</p>',
            ]);

            //$this->multiple_notification($stylist_detailG->device_token, $notifiation_data,$data);
            $googleApiKey = 'AAAA7JZNVfI:APA91bFThKoVHXIiLOEgVL5t9PDIzZFlYZtMU-ow_DtmwvIHBs6xWQfZGxS93ZgKPTX_e9oYnCXLKPXqVX3o82U4fpe1cRF6IzPtjy6fRft_LSHc_xD6H_9RQuL22_1ZANgRTlMbnqYf';
            // print_r($data); die;
            $registrationIds = array($stylist_detailG->device_token);
            $message = "Your have received a new order.";
            $title = "New Order!";
            $msg = array(
                'message' => $message,
                'title' => $title,
                'default_message' => '<p>Keyword/Occasion:' . $booking_detail[0]->booking_title . '</p><p>Date:' . $formated_date . '</p><p>Budget:' . $booking_detail[0]->user_budget . ' ' . $booking_detail[0]->user_currency . '</p>',
                'user_id' => $booking_detail[0]->user_id,
                'stylist_name' => $booking_detail[0]->user_name,
                "user_name" => $user_detailG->name . ' ' . $user_detailG->last_name,
                'booking_id' => $booking_detail[0]->id,
                'stylist_id' => $booking_detail[0]->stylist_id,
                'vibrate' => 1,
                'sound' => 1,
                'type' => '0', //new order receieved
                'priority' => 'high',
                'content_available' => true,
                'booking_detail' => $result12
            );
            if ($stylist_detailG->device_type == 'ios') {
                $fields = array(
                    'registration_ids' => $registrationIds,
                    'notification' => $msg
                );
            } else {
                $fields = array(
                    'registration_ids' => $registrationIds,
                    'data' => $msg
                );
            }

            $headers = array(
                'Authorization: key=' . $googleApiKey,
                'Content-Type: application/json'
            );
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
            $result = curl_exec($ch);


            curl_close($ch);

            DB::table('notifications')->insert([
                'booking_id' => $booking_detail[0]->id,
                'title' => $title,
                'message' => $message,
                'user_id' => $booking_detail[0]->user_id,
                'logged_id' => $stylist_detailG->id,
                'notification_type' => '0',
                'user_name' => $user_detailG->name . ' ' . $user_detailG->last_name,
                'default_message' => '<p>Keyword/Occasion:' . $booking_detail[0]->booking_title . '</p><p>Date:' . $formated_date . '</p><p>Budget:' . $booking_detail[0]->user_budget . ' ' . $booking_detail[0]->user_currency . '</p>',
            ]);

            return json_encode(array('status' => "200", 'message' => "Thanks for appyling! You'll hear from us soon.", 'result' => $result12));
        } else {
            return json_encode(array('status' => "201", 'message' => "You have already book this stylist for this date"));
        }
    }

    //Book stylist END
    //Booking Accpet START
    public function booking_accept_status(Request $request) {
        $booking_id = $request->input('booking_id');
        $booking_accept_status = $request->input('booking_accept_status');
        $booking_id_count = DB::table('bookings')->where('id', $booking_id)->count();
        if ($booking_id_count != '0') {
            $booking_detail = DB::table('bookings')->where('id', $booking_id)->update([
                'booking_accept_status' => $booking_accept_status
            ]);
            $booking_detail33 = DB::table('bookings')->where('id', $booking_id)->get();
            foreach ($booking_detail33 as $booking_details) {
                $result12[] = array(
                    'booking_id' => $booking_id,
                    'stylist_id' => $booking_details->stylist_id,
                    'user_id' => $booking_details->user_id,
                    'user_name' => $booking_details->user_name,
                    'user_email' => $booking_details->user_email,
                    'user_phone' => $booking_details->user_phone,
                    'booking_title' => $booking_details->booking_title,
                    'booking_date' => $booking_details->booking_date,
                    'booking_budget' => $booking_details->user_currency . ' ' . $booking_details->user_budget,
                );
            }

            $user_detailG = DB::table('users')->where('id', $booking_detail33[0]->user_id)->first();
            $stylist_detailG = DB::table('users')->where('id', $booking_detail33[0]->stylist_id)->first();
            //print_r($user_detailG); die;

            if ($booking_accept_status == 'accepted') {
                $googleApiKey = 'AAAA7JZNVfI:APA91bFThKoVHXIiLOEgVL5t9PDIzZFlYZtMU-ow_DtmwvIHBs6xWQfZGxS93ZgKPTX_e9oYnCXLKPXqVX3o82U4fpe1cRF6IzPtjy6fRft_LSHc_xD6H_9RQuL22_1ZANgRTlMbnqYf';
                // print_r($booking_detail33[0]->user_id); die;
                $registrationIds = array($user_detailG->device_token);
                $message = "Your order has been accepted from stylist.";
                $title = "Order Accepted!";
                $msg = array(
                    'message' => $message,
                    'title' => $title,
                    'user_id' => $booking_detail33[0]->user_id,
                    'user_name' => $booking_detail33[0]->user_name,
                    'stylist_name' => $stylist_detailG->name . ' ' . $stylist_detailG->last_name,
                    'booking_id' => $booking_detail33[0]->id,
                    'stylist_id' => $booking_detail33[0]->stylist_id,
                    'vibrate' => 1,
                    'sound' => 1,
                    'type' => '2', //Booking accept status
                    'priority' => 'high',
                    'content_available' => true,
                    ''
                );
                $time = strtotime($booking_detail33[0]->booking_date);
                $formated_date = date('d,M, Y', $time);
                DB::table('notifications')->insert([
                    'booking_id' => $booking_detail33[0]->id,
                    'title' => $title,
                    'message' => $message,
                    'user_id' => $stylist_detailG->id,
                    'logged_id' => $user_detailG->id,
                    'notification_type' => '2',
                    'user_name' => $stylist_detailG->name . ' ' . $stylist_detailG->last_name,
                    'default_message' => '<p>Keyword/Occasion:' . $booking_detail33[0]->booking_title . '</p><p>Date:' . $formated_date . '</p><p>Budget:' . $booking_detail33[0]->user_budget . ' ' . $booking_detail33[0]->user_currency . '</p>',
                ]);

                if ($user_detailG->device_type == 'ios') {
                    $fields = array(
                        'registration_ids' => $registrationIds,
                        'notification' => $msg
                    );
                } else {
                    $fields = array(
                        'registration_ids' => $registrationIds,
                        'data' => $msg
                    );
                }

                $headers = array(
                    'Authorization: key=' . $googleApiKey,
                    'Content-Type: application/json'
                );
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
                $result = curl_exec($ch);


                curl_close($ch);
            } elseif ($booking_accept_status == 'cancelled') {
                $googleApiKey = 'AAAA7JZNVfI:APA91bFThKoVHXIiLOEgVL5t9PDIzZFlYZtMU-ow_DtmwvIHBs6xWQfZGxS93ZgKPTX_e9oYnCXLKPXqVX3o82U4fpe1cRF6IzPtjy6fRft_LSHc_xD6H_9RQuL22_1ZANgRTlMbnqYf';
                // print_r($booking_detail33[0]->user_id); die;
                $registrationIds = array($user_detailG->device_token);
                $message = "Your order has been cancelled from stylist.";
                $title = "Order Cancelled!";
                $msg = array(
                    'message' => $message,
                    'title' => $title,
                    'user_id' => $booking_detail33[0]->user_id,
                    'user_name' => $booking_detail33[0]->user_name,
                    'stylist_name' => $stylist_detailG->name . ' ' . $stylist_detailG->last_name,
                    'booking_id' => $booking_detail33[0]->id,
                    'stylist_id' => $booking_detail33[0]->stylist_id,
                    'vibrate' => 1,
                    'sound' => 1,
                    'type' => '2', //Booking accept status
                    'priority' => 'high',
                    'content_available' => true,
                    ''
                );

                DB::table('notifications')->insert([
                    'booking_id' => $booking_detail33[0]->id,
                    'title' => $title,
                    'message' => $message,
                    'user_id' => $stylist_detailG->id,
                    'logged_id' => $user_detailG->id,
                    'notification_type' => '2',
                    'user_name' => $stylist_detailG->name . ' ' . $stylist_detailG->last_name,
//                'default_message'=>'<p>Keyword/Occasion:' . $booking_detail[0]->booking_title . '</p><p>Date:' . $formated_date . '</p><p>Budget:' . $booking_detail[0]->user_budget . ' ' . $booking_detail[0]->user_currency . '</p>',
                ]);

                if ($user_detailG->device_type == 'ios') {
                    $fields = array(
                        'registration_ids' => $registrationIds,
                        'notification' => $msg
                    );
                } else {
                    $fields = array(
                        'registration_ids' => $registrationIds,
                        'data' => $msg
                    );
                }


                $headers = array(
                    'Authorization: key=' . $googleApiKey,
                    'Content-Type: application/json'
                );
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
                $result = curl_exec($ch);


                curl_close($ch);
            } elseif ($booking_accept_status == 'declined') {
                $googleApiKey = 'AAAA7JZNVfI:APA91bFThKoVHXIiLOEgVL5t9PDIzZFlYZtMU-ow_DtmwvIHBs6xWQfZGxS93ZgKPTX_e9oYnCXLKPXqVX3o82U4fpe1cRF6IzPtjy6fRft_LSHc_xD6H_9RQuL22_1ZANgRTlMbnqYf';
                // print_r($booking_detail33[0]->user_id); die;
                $registrationIds = array($stylist_detailG->device_token);
                $message = "Payment has been declined from user.";
                $title = "Order Declined!";
                $msg = array(
                    'message' => $message,
                    'title' => $title,
                    'user_id' => $booking_detail33[0]->user_id,
                    'user_name' => $booking_detail33[0]->user_name,
                    'stylist_name' => $stylist_detailG->name . ' ' . $stylist_detailG->last_name,
                    'booking_id' => $booking_detail33[0]->id,
                    'stylist_id' => $booking_detail33[0]->stylist_id,
                    'vibrate' => 1,
                    'sound' => 1,
                    'type' => '2', //Booking accept status
                    'priority' => 'high',
                    'content_available' => true,
                    ''
                );

                DB::table('notifications')->insert([
                    'booking_id' => $booking_detail33[0]->id,
                    'title' => $title,
                    'message' => $message,
                    'user_id' => $booking_detail33[0]->user_id,
                    'logged_id' => $stylist_detailG->id,
                    'notification_type' => '2',
                    'user_name' => $booking_detail33[0]->user_name,
//                'default_message'=>'<p>Keyword/Occasion:' . $booking_detail[0]->booking_title . '</p><p>Date:' . $formated_date . '</p><p>Budget:' . $booking_detail[0]->user_budget . ' ' . $booking_detail[0]->user_currency . '</p>',
                ]);

                if ($stylist_detailG->device_type == 'ios') {
                    $fields = array(
                        'registration_ids' => $registrationIds,
                        'notification' => $msg
                    );
                } else {
                    $fields = array(
                        'registration_ids' => $registrationIds,
                        'data' => $msg
                    );
                }
                $headers = array(
                    'Authorization: key=' . $googleApiKey,
                    'Content-Type: application/json'
                );
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
                $result = curl_exec($ch);


                curl_close($ch);
            }


            return json_encode(array('status' => "200", 'message' => "Thanks for accepting", 'result' => $result12));
        } else {
            return json_encode(array('status' => "201", 'message' => "No booking id exist"));
        }
    }

    //Booking Accpet END
    //using booking chat list START
    public function booking_chat_list(Request $request) {
        $user_id = $request->input('user_id');
        $user_type = $request->input('user_type');
        if ($user_type == 'stylist') {
            $booking_count = DB::table('bookings')->where('stylist_id', $user_id)->count();
            $booking_data = DB::table('bookings')->where('stylist_id', $user_id)->orderBy('id', 'desc')->get();
            if ($booking_count != '0') {
//                print_r($booking_data); die;
                foreach ($booking_data as $booking_details) {
                    if ($booking_details->booking_status == '0') {
                        if ($booking_details->booking_accept_status == 'waiting') {
                            $bookingType = 'waiting';
                        }
                        if ($booking_details->booking_accept_status == 'accepted') {
                            $bookingType = 'accepted';
                        }
                        if ($booking_details->booking_accept_status == 'cancelled') {
                            $bookingType = 'cancelled';
                        }
                        if ($booking_details->booking_accept_status == 'time_accepted') {
                            $bookingType = 'time_accepted';
                        }
                        if ($booking_details->booking_accept_status == 'declined') {
                            $bookingType = 'declined';
                        }
                    } else {
                        $bookingType = 'paid';
                    }
                    $stylist_detail = DB::table('users')->where('id', $booking_details->user_id)->first();
                    if (isset($stylist_detail->image) && !empty($stylist_detail->image)) {
                        $image_data = url('../storage/files') . '/' . $stylist_detail->image;
                    } else {
                        $image_data = '';
                    }
                    
                    $rating_count_check =DB::table('rating_to_user')->where('stylist_id', $user_id)->where('booking_id',$booking_details->id)->where('to_whom','to client')->count();
//                    print_r($rating_count_check); die;
                    if($rating_count_check=='0'){
                        $rate_check_status = '';
                    }else{
                        $rate_check_status = '1';
                    }
                    
                    
                    $result12[] = array(
                        'booking_id' => $booking_details->id,
                        'stylist_id' => $booking_details->stylist_id,
                        'stylist_price' => $booking_details->stylist_price,
                        'user_id' => $booking_details->user_id,
                        'user_name' => $stylist_detail->name . ' ' . $stylist_detail->last_name,
                        'user_image' => $image_data,
                        'booking_title' => $booking_details->booking_title,
                        'booking_date' => $booking_details->booking_date,
                        'booking_budget' => $booking_details->user_currency . ' ' . $booking_details->user_budget,
                        'type' => $bookingType,
                        'rate_check'=>$rate_check_status,
                        'default_message' => $booking_details->default_message,
                    );
                }
                return json_encode(array('status' => "200", 'message' => "Chat Record", 'result' => $result12));
            } else {
                return json_encode(array('status' => "201", 'message' => "No chat till now!"));
            }
        } else {
            $booking_count = DB::table('bookings')->where('user_id', $user_id)->count();
            $booking_data = DB::table('bookings')->where('user_id', $user_id)->orderBy('id', 'desc')->get();
           
            if ($booking_count != '0') {
                foreach ($booking_data as $booking_details) {
                    if ($booking_details->booking_status == '0') {
                        if ($booking_details->booking_accept_status == 'waiting') {
                            $bookingType = 'waiting';
                        }
                        if ($booking_details->booking_accept_status == 'accepted') {
                            $bookingType = 'accepted';
                        }
                        if ($booking_details->booking_accept_status == 'cancelled') {
                            $bookingType = 'cancelled';
                        }
                        if ($booking_details->booking_accept_status == 'time_accepted') {
                            $bookingType = 'time_accepted';
                        }
                        if ($booking_details->booking_accept_status == 'declined') {
                            $bookingType = 'declined';
                        }
                    } else {
                        $bookingType = 'paid';
                    }
                    $rating_count_check =DB::table('ratings')->where('user_id', $user_id)->where('booking_id',$booking_details->id)->where('to_whom','to stylist')->count();
                    if($rating_count_check=='0'){
                        $rate_check_status = '';
                    }else{
                        $rate_check_status = '1';
                    }
                    $stylist_detail = DB::table('users')->where('id', $booking_details->stylist_id)->first();
                    if (isset($stylist_detail->image) && !empty($stylist_detail->image)) {
                        $image_data = url('../storage/files') . '/' . $stylist_detail->image;
                    } else {
                        $image_data = '';
                    }
                    
                    
                    $result12[] = array(
                        'booking_id' => $booking_details->id,
                        'stylist_id' => $booking_details->stylist_id,
                        'stylist_price' => $booking_details->stylist_price,
                        'user_name' => $stylist_detail->name . ' ' . $stylist_detail->last_name,
                        'user_image' => $image_data,
                        'user_id' => $booking_details->user_id,
                        'booking_title' => $booking_details->booking_title,
                        'booking_date' => $booking_details->booking_date,
                        'booking_budget' => $booking_details->user_currency . ' ' . $booking_details->user_budget,
                        'type' => $bookingType,
                        'rate_check'=>$rate_check_status
                    );
                }
                return json_encode(array('status' => "200", 'message' => "Chat Record", 'result' => $result12));
            } else {
                return json_encode(array('status' => "200", 'message' => "No chat till now!"));
            }
        }
    }

    //using booking chat list END
    //Booking accept time START
    public function booking_accept_time(Request $request) {
        $booking_id = $request->input('booking_id');
        $booking_time = $request->input('booking_time');
        $booking_data = DB::table('bookings')->where('id', $booking_id)->get();
        if (count($booking_data) != '0') {
            DB::table('bookings')->where('id', $booking_id)->update([
                'booking_time' => $booking_time,
                'booking_accept_status' => 'time_accepted'
            ]);

            $user_detailG = DB::table('users')->where('id', $booking_data[0]->user_id)->first();
            $stylist_detailG = DB::table('users')->where('id', $booking_data[0]->stylist_id)->first();
            //print_r($user_detailG); die;
            $googleApiKey = 'AAAA7JZNVfI:APA91bFThKoVHXIiLOEgVL5t9PDIzZFlYZtMU-ow_DtmwvIHBs6xWQfZGxS93ZgKPTX_e9oYnCXLKPXqVX3o82U4fpe1cRF6IzPtjy6fRft_LSHc_xD6H_9RQuL22_1ZANgRTlMbnqYf';
            // print_r($booking_detail33[0]->user_id); die;
            $registrationIds = array($user_detailG->device_token);
            $message = "Your order Time has been scheduled successfully.";
            $title = "Order Time Scheduled!";
            $time = strtotime($booking_data[0]->booking_date);
            $formated_date = date('d,M, Y', $time);
            $msg = array(
                'message' => $message,
                'title' => $title,
                'user_id' => $booking_data[0]->user_id,
                'user_name' => $user_detailG->name . ' ' . $user_detailG->last_name,
                'stylist_name' => $stylist_detailG->name . ' ' . $stylist_detailG->last_name,
                'booking_id' => $booking_data[0]->id,
                'stylist_id' => $booking_data[0]->stylist_id,
                'stylist_price' => $booking_data[0]->stylist_price,
                'vibrate' => 1,
                'sound' => 1,
                'type' => '1', //status 1 for order time accepted
                'priority' => 'high',
                'content_available' => true,
                'time_accepted' => 'success',
                'default_message' => '<p>Keyword/Occasion:' . $booking_data[0]->booking_title . '</p><p>Date:' . $formated_date . '</p><p>Budget:' . $booking_data[0]->user_budget . ' ' . $booking_data[0]->user_currency . '</p>',
            );
            $time = strtotime($booking_data[0]->booking_date);
            $formated_date = date('d,M, Y', $time);
            DB::table('notifications')->insert([
                'booking_id' => $booking_data[0]->id,
                'title' => $title,
                'message' => $message,
                'user_id' => $stylist_detailG->id,
                'logged_id' => $user_detailG->id,
                'notification_type' => '1',
                'user_name' => $stylist_detailG->name . ' ' . $stylist_detailG->last_name,
                'default_message' => '<p>Keyword/Occasion:' . $booking_data[0]->booking_title . '</p><p>Date:' . $formated_date . '</p><p>Budget:' . $booking_data[0]->user_budget . ' ' . $booking_data[0]->user_currency . '</p>',
            ]);

            //print_r($msg); die;
            if ($user_detailG->device_type == 'ios') {
                $fields = array(
                    'registration_ids' => $registrationIds,
                    'notification' => $msg
                );
            } else {
                $fields = array(
                    'registration_ids' => $registrationIds,
                    'data' => $msg
                );
            }

            $headers = array(
                'Authorization: key=' . $googleApiKey,
                'Content-Type: application/json'
            );
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
            $result = curl_exec($ch);


            curl_close($ch);


            $googleApiKey = 'AAAA7JZNVfI:APA91bFThKoVHXIiLOEgVL5t9PDIzZFlYZtMU-ow_DtmwvIHBs6xWQfZGxS93ZgKPTX_e9oYnCXLKPXqVX3o82U4fpe1cRF6IzPtjy6fRft_LSHc_xD6H_9RQuL22_1ZANgRTlMbnqYf';
            // print_r($booking_detail33[0]->user_id); die;
            $registrationIds = array($stylist_detailG->device_token);
            $message = "Your order Time has been scheduled successfully.";
            $title = "Order Time Scheduled!";
            $time = strtotime($booking_data[0]->booking_date);
            $formated_date = date('d,M, Y', $time);
            $msg = array(
                'message' => $message,
                'title' => $title,
                'user_id' => $booking_data[0]->user_id,
                'user_name' => $user_detailG->name . ' ' . $user_detailG->last_name,
                'stylist_name' => $stylist_detailG->name . ' ' . $stylist_detailG->last_name,
                'booking_id' => $booking_data[0]->id,
                'stylist_id' => $booking_data[0]->stylist_id,
                'stylist_price' => $booking_data[0]->stylist_price,
                'vibrate' => 1,
                'sound' => 1,
                'type' => '1', //status 1 for order time accepted
                'priority' => 'high',
                'content_available' => true,
                'time_accepted' => 'success',
                'default_message' => '<p>Keyword/Occasion:' . $booking_data[0]->booking_title . '</p><p>Date:' . $formated_date . '</p><p>Budget:' . $booking_data[0]->user_budget . ' ' . $booking_data[0]->user_currency . '</p>',
            );
            $time = strtotime($booking_data[0]->booking_date);
            $formated_date = date('d,M, Y', $time);
            DB::table('notifications')->insert([
                'booking_id' => $booking_data[0]->id,
                'title' => $title,
                'message' => $message,
                'logged_id' => $stylist_detailG->id,
                'user_id' => $user_detailG->id,
                'notification_type' => '1',
                'user_name' => $user_detailG->name . ' ' . $user_detailG->last_name,
                'default_message' => '<p>Keyword/Occasion:' . $booking_data[0]->booking_title . '</p><p>Date:' . $formated_date . '</p><p>Budget:' . $booking_data[0]->user_budget . ' ' . $booking_data[0]->user_currency . '</p>',
            ]);
            if ($stylist_detailG->device_type == 'ios') {
                $fields = array(
                    'registration_ids' => $registrationIds,
                    'notification' => $msg
                );
            } else {
                $fields = array(
                    'registration_ids' => $registrationIds,
                    'data' => $msg
                );
            }
            $headers = array(
                'Authorization: key=' . $googleApiKey,
                'Content-Type: application/json'
            );
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
            $result = curl_exec($ch);


            curl_close($ch);

            $res['status'] = "200";
            $res['msg'] = "Your time has been scheduled successfully.";
            return json_encode($res);
        } else {
            $res['status'] = "201";
            $res['msg'] = "No booking exist with this booking id.";
            return json_encode($res);
        }
    }

    //Booking accept time END
    //Save post stylist START
    public function save_post_stylist(Request $request) {
        $user_id = $request->input('user_id');
        $stylist_id = $request->input('stylist_id');
        $saved_count = DB::table('saved_stylist')->where('user_id', $user_id)->where('stylist_id', $stylist_id)->count();


        if ($saved_count == '0') {
            DB::table('saved_stylist')->insert([
                'user_id' => $user_id,
                'stylist_id' => $stylist_id,
                'status' => '1',
            ]);
            $res['status'] = "200";
            $res['msg'] = "You have saved this stylist successfully.";
            return json_encode($res);
        } else {
            if ($request->input('status') == '0') {
                DB::table('saved_stylist')->where('user_id', $user_id)->where('stylist_id', $stylist_id)->update([
                    'status' => '0'
                ]);
                $res['status'] = "200";
                $res['msg'] = "You unsaved this stylist successfully.";
                return json_encode($res);
            } else {
                DB::table('saved_stylist')->where('user_id', $user_id)->where('stylist_id', $stylist_id)->update([
                    'status' => '1'
                ]);
                $res['status'] = "200";
                $res['msg'] = "You have saved this stylist successfully.";
                return json_encode($res);
            }
        }
    }

    //Save post stylist END
    //saved stylist list for user START
    public function saved_stylist(Request $request) {
        $user_id = $request->input('user_id');
        $saved_user_count = DB::table('saved_stylist')->where('user_id', $user_id)->where('status', '1')->count();

        //print_r($saved_user_count); die;
        if ($saved_user_count == '0') {
            $res['status'] = "201";
            $res['msg'] = "No record found for this user";

            return json_encode($res);
        } else {
            $saved_stylists = DB::table('saved_stylist')->where('user_id', $user_id)->where('status', '1')->get();
            foreach ($saved_stylists as $saved_stylist) {
                $stylist_detail = DB::table('users')->where('id', $saved_stylist->stylist_id)->first();
                if (isset($stylist_detail->image) && !empty($stylist_detail->image)) {
                    $image_data = url('../storage/files') . '/' . $stylist_detail->image;
                } else {
                    $image_data = '';
                }
                $result[] = array(
                    'id' => $saved_stylist->id,
                    'stylist_id' => $saved_stylist->stylist_id,
                    'status' => $saved_stylist->status,
                    'stylist_name' => $stylist_detail->name . ' ' . $stylist_detail->last_name,
                    'stylist_image' => $image_data,
                );
            }
            return json_encode(array('status' => "200", 'message' => "Saved stylists", 'result' => $result));
        }
    }

    //saved stylist list for user END
    //stylist filter START
    public function filter_stylist(Request $request) {
        $user_id = $request->input('user_id');
        $min_price = $request->input('min_price');
        $max_price = $request->input('max_price');
        $location = $request->input('location');
        $show_urgent = $request->input('show_urgent');
        $stylist_language = $request->input('language');
        $latitude = $request->input('lat');
        $longitude = $request->input('lng');
        if (!empty($min_price) && !empty($max_price) && empty($location) && empty($show_urgent)) {

            if ($stylist_language != 'any') {
                $data = DB::select("select * from `users` where `type` = 'stylist' and `id`!=$user_id  and `user_language`='$stylist_language' and `user_price` between $min_price and $max_price");
            } else {
                $data = DB::select("select * from `users` where `type` = 'stylist' and `id`!=$user_id  and `user_price` between $min_price and $max_price");
            }

            //print_r($data); die;
            foreach ($data as $datas) {
                //for stylist
                if ($datas->type == 'stylist') {

                    $all_user = User::where('id', $datas->id)->first();
                    if (isset($all_user->image) && !empty($all_user->image)) {
                        $image_data = url('../storage/files/') . '/' . $all_user->image;
                    } else {
                        $image_data = '';
                    }

                    $shoping_loc_count = ShoppingLocation::where('user_id', $all_user->id)->count();
//                  $shoping_loc_count = ShoppingLocation::select(DB::raw('*, ( 6367 * acos( cos( radians(' . $latitude . ') ) * cos( radians( lat ) ) * cos( radians( lng ) - radians(' . $longitude . ') ) + sin( radians(' . $latitude . ') ) * sin( radians( lat ) ) ) ) AS distance'))
//                ->having('distance', '<', 25)
//                ->where('user_id',  $all_user->id)
//                ->orderBy('distance')
//                ->count();
                    $shoping_loc_get = ShoppingLocation::select(DB::raw('*, ( 6367 * acos( cos( radians(' . $latitude . ') ) * cos( radians( lat ) ) * cos( radians( lng ) - radians(' . $longitude . ') ) + sin( radians(' . $latitude . ') ) * sin( radians( lat ) ) ) ) AS distance'))
                            ->having('distance', '<', 25)
                            ->where('user_id', $all_user->id)
                            ->orderBy('distance')
                            ->get();
//                    $shoping_loc_get = ShoppingLocation::where('user_id', $all_user->id)
//                            ->orderBy('id', 'desc')
//                            ->get();
                    //print_r($shoping_loc_get); die;
                    if ($shoping_loc_count == '0') {
                        $loc_data2 = array();
                    } else {
                        $loc_data2 = [];
                        foreach ($shoping_loc_get as $shoping_loc) {
                            $loc_data2[] = array(
                                'id' => $shoping_loc->id,
                                'lat' => $shoping_loc->lat,
                                'lng' => $shoping_loc->lng,
                                'city' => $shoping_loc->city,
                                'distance' => round($shoping_loc->distance) . ' km',
                            );
                        }
                    }
                    //Get rating of specialist START
                    $get_rating_data = DB::table('ratings')->select('rate')->where('stylist_id', $all_user->id)->get();
                    $get_data_rate_total = 0;
                    foreach ($get_rating_data as $get_rating_datas) {
                        $get_data_rate_total += $get_rating_datas->rate;
                    }

                    if (count($get_rating_data) != '0') {
                        $average = $get_data_rate_total / count($get_rating_data);
                        $count_rating = count($get_rating_data);
                    } else {
                        $average = '0';
                        $count_rating = '0';
                    }
                    //Get rating of specialist END

                    $saved_count_check = DB::table('saved_stylist')->where('user_id', $request->input('user_id'))->where('stylist_id', $all_user->id)->where('status', '1')->first();
                    //print_r($saved_count_check)); die;
                    if (isset($saved_count_check) && !empty($saved_count_check)) {
                        $saved_check = $saved_count_check->status;
                    } else {
                        $saved_check = '0';
                    }

                    $all_pick_data[] = array(
                        'id' => $all_user->id,
                        'first_name' => $all_user->name,
                        'last_name' => $all_user->last_name,
                        'email' => $all_user->email,
                        'fav' => $saved_check,
                        'image' => $image_data,
                        'image' => $image_data,
                        'image' => $image_data,
                        'user_location' => array_unique($loc_data2, SORT_REGULAR),
                        'rating_percentage' => ceil($average),
                        'rating_count' => $count_rating,
                    );


                    $user_Data = array_unique($all_pick_data, SORT_REGULAR);
                }
            }

            if (count($data) == '0') {
                return json_encode(array('status' => "201", 'message' => 'No record found'));
            }
        } elseif (!empty($min_price) && !empty($max_price) && !empty($location) && empty($show_urgent)) {
            $loc = json_decode($location);
            //print_r($loc); die;
            foreach ($loc as $locs) {
                $shoping_loc_get = ShoppingLocation::select(DB::raw('*, ( 6367 * acos( cos( radians(' . $locs->lat . ') ) * cos( radians( lat ) ) * cos( radians( lng ) - radians(' . $locs->lng . ') ) + sin( radians(' . $locs->lat . ') ) * sin( radians( lat ) ) ) ) AS distance'))
                        ->having('distance', '<', 25)
                        ->where('user_id', '!=', $user_id)
                        ->orderBy('distance')
                        ->get();
            }
            $i = 0;
            //print_r($shoping_loc_get); die;
            foreach ($shoping_loc_get as $shoping_loc_gets) {
                $users[$i]['loc_id'] = $shoping_loc_gets->id;
                $users[$i]['user_id'] = $shoping_loc_gets->user_id;
                $i++;
            }
            $all_users_data = array_unique($users, SORT_REGULAR);

            if ($stylist_language != 'any') {
                foreach ($all_users_data as $key1 => $data_unset) {
                    $get_user_final = DB::table('users')->where('id', $data_unset['user_id'])->where('user_language', $stylist_language)->count();
                    if ($get_user_final == 0) {
                        unset($all_users_data[$key1]);
                    }
                }
                if (count($all_users_data) == '0') {
                    return json_encode(array('status' => "201", 'message' => 'No record found'));
                }
            }

            //print_r($all_users_data); die; 
            foreach ($all_users_data as $all_user1) {

//               ->where('user_language',$stylist_language)
                $all_user = User::where('id', $all_user1['user_id'])->first();
                if (isset($all_user->image) && !empty($all_user->image)) {
                    $image_data = url('../storage/files/') . '/' . $all_user->image;
                } else {
                    $image_data = '';
                }

                $shoping_loc_count = ShoppingLocation::where('id', $all_user1['loc_id'])->count();
                $shoping_loc_as = ShoppingLocation::where('id', $all_user1['loc_id'])->first();
//                  $shoping_loc_count = ShoppingLocation::select(DB::raw('*, ( 6367 * acos( cos( radians(' . $latitude . ') ) * cos( radians( lat ) ) * cos( radians( lng ) - radians(' . $longitude . ') ) + sin( radians(' . $latitude . ') ) * sin( radians( lat ) ) ) ) AS distance'))
//                ->having('distance', '<', 25)
//                ->where('user_id',  $all_user->id)
//                ->orderBy('distance')
//                ->count();
//                $shoping_loc_get = ShoppingLocation::where('user_id', $all_user->id)->get();
                $shoping_loc_get = ShoppingLocation::select(DB::raw('*, ( 6367 * acos( cos( radians(' . $shoping_loc_as->lat . ') ) * cos( radians( lat ) ) * cos( radians( lng ) - radians(' . $shoping_loc_as->lng . ') ) + sin( radians(' . $shoping_loc_as->lat . ') ) * sin( radians( lat ) ) ) ) AS distance'))
                        ->having('distance', '<', 25)
                        ->where('user_id', $all_user->id)
                        ->orderBy('distance')
                        ->get();
                //print_r($shoping_loc_get); die;
                if ($shoping_loc_count == '0') {
                    $loc_data2 = array();
                } else {
                    $loc_data2 = [];
                    foreach ($shoping_loc_get as $shoping_loc) {
                        $loc_data2[] = array(
                            'id' => $shoping_loc->id,
                            'lat' => $shoping_loc->lat,
                            'lng' => $shoping_loc->lng,
                            'city' => $shoping_loc->city,
                            'distance' => round($shoping_loc->distance) . ' km',
                        );
                    }
                }
                //Get rating of specialist START
                $get_rating_data = DB::table('ratings')->select('rate')->where('stylist_id', $all_user->id)->get();
                $get_data_rate_total = 0;
                foreach ($get_rating_data as $get_rating_datas) {
                    $get_data_rate_total += $get_rating_datas->rate;
                }

                if (count($get_rating_data) != '0') {
                    $average = $get_data_rate_total / count($get_rating_data);
                    $count_rating = count($get_rating_data);
                } else {
                    $average = '0';
                    $count_rating = '0';
                }
                //Get rating of specialist END


                $saved_count_check = DB::table('saved_stylist')->where('user_id', $request->input('user_id'))->where('stylist_id', $all_user->id)->where('status', '1')->first();
                //print_r($saved_count_check)); die;
                if (isset($saved_count_check) && !empty($saved_count_check)) {
                    $saved_check = $saved_count_check->status;
                } else {
                    $saved_check = '0';
                }
                $all_pick_data[] = array(
                    'id' => $all_user->id,
                    'first_name' => $all_user->name,
                    'last_name' => $all_user->last_name,
                    'email' => $all_user->email,
                    'fav' => $saved_check,
                    'image' => $image_data,
                    'image' => $image_data,
                    'image' => $image_data,
                    'user_location' => array_unique($loc_data2, SORT_REGULAR),
                    'rating_percentage' => ceil($average),
                    'rating_count' => $count_rating,
                );


                $all_pick_datas1 = array_unique($all_pick_data, SORT_REGULAR);
                $user_Data = array_values($all_pick_datas1);
            }
            //print_r($data); die; 
        } elseif (!empty($min_price) && !empty($max_price) && !empty($location) && !empty($show_urgent)) {
            $loc = json_decode($location);
            //print_r($loc); die;
            foreach ($loc as $locs) {
                $shoping_loc_get = ShoppingLocation::select(DB::raw('*, ( 6367 * acos( cos( radians(' . $locs->lat . ') ) * cos( radians( lat ) ) * cos( radians( lng ) - radians(' . $locs->lng . ') ) + sin( radians(' . $locs->lat . ') ) * sin( radians( lat ) ) ) ) AS distance'))
                        ->having('distance', '<', 25)
                        ->where('user_id', '!=', $user_id)
                        ->orderBy('distance')
                        ->get();
            }
            $i = 0;
            //print_r($shoping_loc_get); die;
            foreach ($shoping_loc_get as $shoping_loc_gets) {
                $users[$i]['loc_id'] = $shoping_loc_gets->id;
                $users[$i]['user_id'] = $shoping_loc_gets->user_id;
                $i++;
            }
            $all_users_data1 = array_unique($users, SORT_REGULAR);

            if ($stylist_language != 'any') {
                foreach ($all_users_data1 as $key1 => $data_unset) {
                    $get_user_final = DB::table('users')->where('id', $data_unset['user_id'])->where('user_language', $stylist_language)->count();
                    if ($get_user_final == 0) {
                        unset($all_users_data1[$key1]);
                    }
                }
                if (count($all_users_data1) == '0') {
                    return json_encode(array('status' => "201", 'message' => 'No record found'));
                }
            }

//            print_r($all_users_data1); die;
            foreach ($all_users_data1 as $key => $all_users_datas) {
                $current_date = date('Y-m-d');
                $tomorrow_date = date('Y-m-d', strtotime('+1 day' . $current_date));
                $data_get_count = DB::table('bookings')->where('stylist_id', $all_users_datas['user_id'])->whereDate('booking_date', '!=', $current_date)->whereDate('booking_date', '!=', $tomorrow_date)->count();
                if ($data_get_count > 0) {
                    unset($all_users_data1[$key]);
                }
            }
            $all_users_data = array_values($all_users_data1);
            //print_r($all_users_data); die;
            foreach ($all_users_data as $all_user1) {
                $all_user = User::where('id', $all_user1['user_id'])->first();
                if (isset($all_user->image) && !empty($all_user->image)) {
                    $image_data = url('../storage/files/') . '/' . $all_user->image;
                } else {
                    $image_data = '';
                }

                $shoping_loc_count = ShoppingLocation::where('id', $all_user1['loc_id'])->count();
                $shoping_loc_as = ShoppingLocation::where('id', $all_user1['loc_id'])->first();
//                  $shoping_loc_count = ShoppingLocation::select(DB::raw('*, ( 6367 * acos( cos( radians(' . $latitude . ') ) * cos( radians( lat ) ) * cos( radians( lng ) - radians(' . $longitude . ') ) + sin( radians(' . $latitude . ') ) * sin( radians( lat ) ) ) ) AS distance'))
//                ->having('distance', '<', 25)
//                ->where('user_id',  $all_user->id)
//                ->orderBy('distance')
//                ->count();
//                $shoping_loc_get = ShoppingLocation::where('user_id', $all_user->id)->get();
                $shoping_loc_get = ShoppingLocation::select(DB::raw('*, ( 6367 * acos( cos( radians(' . $shoping_loc_as->lat . ') ) * cos( radians( lat ) ) * cos( radians( lng ) - radians(' . $shoping_loc_as->lng . ') ) + sin( radians(' . $shoping_loc_as->lat . ') ) * sin( radians( lat ) ) ) ) AS distance'))
                        ->having('distance', '<', 25)
                        ->where('user_id', $all_user->id)
                        ->orderBy('distance')
                        ->get();
                //print_r($shoping_loc_get); die;
                if ($shoping_loc_count == '0') {
                    $loc_data2 = array();
                } else {
                    $loc_data2 = [];
                    foreach ($shoping_loc_get as $shoping_loc) {
                        $loc_data2[] = array(
                            'id' => $shoping_loc->id,
                            'lat' => $shoping_loc->lat,
                            'lng' => $shoping_loc->lng,
                            'city' => $shoping_loc->city,
                            'distance' => round($shoping_loc->distance) . ' km',
                        );
                    }
                }
                //Get rating of specialist START
                $get_rating_data = DB::table('ratings')->select('rate')->where('stylist_id', $all_user->id)->get();
                $get_data_rate_total = 0;
                foreach ($get_rating_data as $get_rating_datas) {
                    $get_data_rate_total += $get_rating_datas->rate;
                }

                if (count($get_rating_data) != '0') {
                    $average = $get_data_rate_total / count($get_rating_data);
                    $count_rating = count($get_rating_data);
                } else {
                    $average = '0';
                    $count_rating = '0';
                }
                //Get rating of specialist END
                $saved_count_check = DB::table('saved_stylist')->where('user_id', $request->input('user_id'))->where('stylist_id', $all_user->id)->where('status', '1')->first();
                //print_r($saved_count_check)); die;
                if (isset($saved_count_check) && !empty($saved_count_check)) {
                    $saved_check = $saved_count_check->status;
                } else {
                    $saved_check = '0';
                }


                $all_pick_data[] = array(
                    'id' => $all_user->id,
                    'first_name' => $all_user->name,
                    'last_name' => $all_user->last_name,
                    'email' => $all_user->email,
                    'fav' => $saved_check,
                    'image' => $image_data,
                    'image' => $image_data,
                    'image' => $image_data,
                    'user_location' => array_unique($loc_data2, SORT_REGULAR),
                    'rating_percentage' => ceil($average),
                    'rating_count' => $count_rating,
                );


                $all_pick_datas1 = array_unique($all_pick_data, SORT_REGULAR);
                $user_Data = array_values($all_pick_datas1);
            }
        } else {

            if ($stylist_language != 'any') {
                $data = DB::select("select * from `users` where `type` = 'stylist' and `id`!=$user_id  and `user_language`='$stylist_language' and `user_price` between $min_price and $max_price");
            } else {
                $data = DB::select("select * from `users` where `type` = 'stylist' and `id`!=$user_id and `user_price` between $min_price and $max_price");
            }




            //print_r($data); die;
            foreach ($data as $datas) {
                //for stylist
                if ($datas->type == 'stylist') {

                    $all_user = User::where('id', $datas->id)->first();
                    if (isset($all_user->image) && !empty($all_user->image)) {
                        $image_data = url('../storage/files/') . '/' . $all_user->image;
                    } else {
                        $image_data = '';
                    }
//                    echo $latitude;
//                    echo $longitude; die;
                    $shoping_loc_count = ShoppingLocation::where('user_id', $all_user->id)->count();
//                  $shoping_loc_count = ShoppingLocation::select(DB::raw('*, ( 6367 * acos( cos( radians(' . $latitude . ') ) * cos( radians( lat ) ) * cos( radians( lng ) - radians(' . $longitude . ') ) + sin( radians(' . $latitude . ') ) * sin( radians( lat ) ) ) ) AS distance'))
//                ->having('distance', '<', 25)
//                ->where('user_id',  $all_user->id)
//                ->orderBy('distance')
//                ->count();
//                $shoping_loc_get = ShoppingLocation::where('user_id', $all_user->id)->get();
                    $shoping_loc_get = ShoppingLocation::select(DB::raw('*, ( 6367 * acos( cos( radians(' . $latitude . ') ) * cos( radians( lat ) ) * cos( radians( lng ) - radians(' . $longitude . ') ) + sin( radians(' . $latitude . ') ) * sin( radians( lat ) ) ) ) AS distance'))
                            ->having('distance', '<', 25)
                            ->where('user_id', $all_user->id)
                            ->orderBy('distance')
                            ->get();
                    //print_r($shoping_loc_get); die;
                    if ($shoping_loc_count == '0') {
                        $loc_data2 = array();
                    } else {
                        $loc_data2 = [];
                        foreach ($shoping_loc_get as $shoping_loc) {
                            $loc_data2[] = array(
                                'id' => $shoping_loc->id,
                                'lat' => $shoping_loc->lat,
                                'lng' => $shoping_loc->lng,
                                'city' => $shoping_loc->city,
                                'distance' => round($shoping_loc->distance) . ' km',
                            );
                        }
                    }
                    //Get rating of specialist START
                    $get_rating_data = DB::table('ratings')->select('rate')->where('stylist_id', $all_user->id)->get();
                    $get_data_rate_total = 0;
                    foreach ($get_rating_data as $get_rating_datas) {
                        $get_data_rate_total += $get_rating_datas->rate;
                    }

                    if (count($get_rating_data) != '0') {
                        $average = $get_data_rate_total / count($get_rating_data);
                        $count_rating = count($get_rating_data);
                    } else {
                        $average = '0';
                        $count_rating = '0';
                    }
                    //Get rating of specialist END


                    $saved_count_check = DB::table('saved_stylist')->where('user_id', $request->input('user_id'))->where('stylist_id', $all_user->id)->where('status', '1')->first();
                    //print_r($saved_count_check)); die;
                    if (isset($saved_count_check) && !empty($saved_count_check)) {
                        $saved_check = $saved_count_check->status;
                    } else {
                        $saved_check = '0';
                    }
                    $all_pick_data[] = array(
                        'id' => $all_user->id,
                        'first_name' => $all_user->name,
                        'last_name' => $all_user->last_name,
                        'email' => $all_user->email,
                        'fav' => $saved_check,
                        'image' => $image_data,
                        'image' => $image_data,
                        'image' => $image_data,
                        'user_location' => array_unique($loc_data2, SORT_REGULAR),
                        'rating_percentage' => ceil($average),
                        'rating_count' => $count_rating,
                    );


                    $user_Data = array_unique($all_pick_data, SORT_REGULAR);
                }
            }

            if (count($data) == '0') {
                return json_encode(array('status' => "201", 'message' => 'No record found'));
            }
        }


        return json_encode(array('status' => "200", 'message' => 'Filter data', 'result' => $user_Data));
    }

    //stylist filter END
    //First Payment after Chat START
    public function first_payment(Request $request) {
        $booking_id = $request->input('booking_id');
        $payment_id = $request->input('payment_id');
        $payment_amount = $request->input('payment_amount');
        $payment_currency = $request->input('payment_currency');
        $wallet_amount = $request->input('wallet_amount');
        $payment_type = $request->input('payment_type');
        $booking_exist_check = DB::table('bookings')->where('id', $booking_id)->count();
        $first_pay_count = DB::table('first_payment')->where('booking_id', $booking_id)->where('payment_id', $payment_id)->count();
        if ($booking_exist_check != '0') {
            if ($first_pay_count == '0') {
                DB::table('first_payment')->insert([
                    'booking_id' => $booking_id,
                    'payment_id' => $payment_id,
                    'payment_amount' => $payment_amount,
                    'payment_currency' => $payment_currency,
                    'wallet_amount'=>$wallet_amount,
                    'payment_type'=>$payment_type
                ]);
                $booking_detail = DB::table('bookings')->where('id', $booking_id)->first();
                if($payment_type=='wallet'){
                    //$booking_data = DB::table('bookings')->where('id', $booking_id)->first();
                    DB::table('wallet')->insert([
                        'user_id' => $booking_detail->user_id,
                        'amount' => $wallet_amount,
                        'withdrawal_status' => 'out'
                    ]);
                }
//                else{
//                    //$booking_data = DB::table('bookings')->where('id', $booking_id)->first();
//                    DB::table('wallet')->insert([
//                        'user_id' => $booking_detail->user_id,
//                        'amount' => $wallet_amount,
//                        'withdrawal_status' => 'out'
//                    ]);
//                }
                
                
                DB::table('bookings')->where('id', $booking_id)->update([
                    'booking_status' => '1',
                    'booking_accept_status' => 'time_accepted',
                ]);
                $userData = DB::table('users')->where('id', $booking_detail->user_id)->first();
                $stylistData = DB::table('users')->where('id', $booking_detail->stylist_id)->first();
                $googleApiKey = 'AAAA7JZNVfI:APA91bFThKoVHXIiLOEgVL5t9PDIzZFlYZtMU-ow_DtmwvIHBs6xWQfZGxS93ZgKPTX_e9oYnCXLKPXqVX3o82U4fpe1cRF6IzPtjy6fRft_LSHc_xD6H_9RQuL22_1ZANgRTlMbnqYf';
                // print_r($data); die;
                $registrationIds = array($userData->device_token, $stylistData->device_token);
                $message = "Congrats! your booking payment for" . $booking_detail->booking_title . " has been confirmed successfully.";
                $title = "Booking Payment Confirmed!";
                $msg = array(
                    'message' => $message,
                    'title' => $title,
                    'user_id' => $booking_detail->user_id,
                    'stylist_name' => $booking_detail->user_name,
                    "user_name" => $userData->name . ' ' . $userData->last_name,
                    'booking_id' => $booking_detail->id,
                    'stylist_id' => $booking_detail->stylist_id,
                    'stylist_name' => $stylistData->name . ' ' . $stylistData->last_name,
                    'vibrate' => 1,
                    'sound' => 1,
                    'type' => '112', //Booking first payment type
                    'priority' => 'high',
                    'content_available' => true,
                );
                $fields = array(
                    'registration_ids' => $registrationIds,
                    'data' => $msg,
                    'notification' => $msg
                );
                $headers = array(
                    'Authorization: key=' . $googleApiKey,
                    'Content-Type: application/json'
                );

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
                $result = curl_exec($ch);


                curl_close($ch);

                DB::table('notifications')->insert([
                    'booking_id' => $booking_detail->id,
                    'title' => $title,
                    'message' => $message,
                    'logged_id' => $booking_detail->user_id,
                    'user_id' => $booking_detail->stylist_id,
                    'notification_type' => '112',
                    'user_name' => $booking_detail->user_name,
//                'default_message'=>'<p>Keyword/Occasion:' . $booking_detail[0]->booking_title . '</p><p>Date:' . $formated_date . '</p><p>Budget:' . $booking_detail[0]->user_budget . ' ' . $booking_detail[0]->user_currency . '</p>',
                ]);

                DB::table('notifications')->insert([
                    'booking_id' => $booking_detail->id,
                    'title' => $title,
                    'message' => $message,
                    'logged_id' => $booking_detail->stylist_id,
                    'user_id' => $booking_detail->user_id,
                    'notification_type' => '112',
                    'user_name' => $booking_detail->user_name,
//                'default_message'=>'<p>Keyword/Occasion:' . $booking_detail[0]->booking_title . '</p><p>Date:' . $formated_date . '</p><p>Budget:' . $booking_detail[0]->user_budget . ' ' . $booking_detail[0]->user_currency . '</p>',
                ]);

                $res['status'] = "200";
                $res['msg'] = "Congrats! your booking has been confirmed";

                return json_encode($res);
            } else {
                $res['status'] = "201";
                $res['msg'] = "You have already pay your first installment for this booking";

                return json_encode($res);
            }
        } else {
            $res['status'] = "201";
            $res['msg'] = "No booking exist with this id";

            return json_encode($res);
        }
    }

    //First Payment after Chat END
    //My bill client section START
    public function my_bills(Request $request) {
        $user_id = $request->input('user_id');
        $user_type = $request->input('user_type');
        if ($user_type == 'stylist') {
            $check_bill_count = DB::table('bookings')->where('stylist_id', $user_id)->where('booking_status', '1')->count();
            if ($check_bill_count == '0') {
                $res['status'] = "201";
                $res['msg'] = "No booking made by your till now.";
                return json_encode($res);
            } else {
                $get_bills = DB::table('bookings')->where('stylist_id', $user_id)->where('booking_status', '1')->get();

                foreach ($get_bills as $get_bill) {

                    //print_r($get_bill->booking_date); die;
                    $stylist_detail = DB::table('users')->where('id', $get_bill->user_id)->first();
                    if (isset($stylist_detail->image) && !empty($stylist_detail->image)) {
                        $image_data = url('../storage/files/') . '/' . $stylist_detail->image;
                    } else {
                        $image_data = '';
                    }
                    $booking_date_format = date('d,M,Y', strtotime($get_bill->booking_date));


                    $get_payments = DB::table('first_payment')->where('booking_id', $get_bill->id)->get();
                    if (count($get_payments) != '0') {
                        foreach ($get_payments as $get_payment) {
                            $all_payments[] = (array) $get_payment;
                        }
                        $total_amount_earned = 0;
                        foreach ($all_payments as $all_payment) {
                            $total_amount_earned += $all_payment['payment_amount'];
                        }
                        $stylist_earned = "$total_amount_earned";
                    } else {
                        $stylist_earned = "0";
                    }



                    $re_book = DB::table('meeting_rescheduled')->where('booking_id', $get_bill->id)->get();
                    if (count($re_book) != '0') {
                        foreach ($re_book as $re_books) {
                            $total_times[] = (array) $re_books;
                        }
                        $seconds = 0;
                        foreach ($total_times as $array_timess) {
                            list($hour, $minute, $second) = explode(':', $array_timess['time']);
                            $seconds += $hour * 3600;
                            $seconds += $minute * 60;
                            $seconds += $second;
                        }
                        $hours = floor($seconds / 3600);
                        $seconds -= $hours * 3600;
                        $minutes = floor($seconds / 60);
                        $seconds -= $minutes * 60;
                        if ($seconds < 9) {
                            $seconds = "0" . $seconds;
                        }
                        if ($minutes < 9) {
                            $minutes = "0" . $minutes;
                        }
                        if ($hours < 9) {
                            $hours = "0" . $hours + 1;
                        }
                        $total_worked_time = "{$hours}:{$minutes}:{$seconds}";
                    } else {
                        $total_worked_time = '1';
                    }

                    $result[] = array(
                        'booking_id' => $get_bill->id,
                        'booking_title' => $get_bill->booking_title,
                        'booking_location' => $get_bill->booking_location,
                        'booking_date' => $booking_date_format,
                        'booking_time' => '1 hr',
                        'booking_payment_paid' => $stylist_detail->user_currency . ' ' . $stylist_detail->user_price,
                        'user_name' => $stylist_detail->name . ' ' . $stylist_detail->last_name,
                        'user_image' => $image_data,
                        'total_worked_time' => $total_worked_time,
                        'total_earned' => $stylist_earned,
                    );
                }
                return json_encode(array('status' => "200", 'message' => 'all term and conditions', 'result' => $result));
            }
        } else {
            $check_bill_count = DB::table('bookings')->where('user_id', $user_id)->where('booking_status', '1')->count();
            if ($check_bill_count == '0') {
                $res['status'] = "201";
                $res['msg'] = "No booking made by your till now.";
                return json_encode($res);
            } else {
                $get_bills = DB::table('bookings')->where('user_id', $user_id)->where('booking_status', '1')->get();
                foreach ($get_bills as $get_bill) {

                    $user_detail = DB::table('users')->where('id', $get_bill->stylist_id)->first();
                    if (isset($user_detail->image) && !empty($user_detail->image)) {
                        $image_data = url('../storage/files/') . '/' . $user_detail->image;
                    } else {
                        $image_data = '';
                    }
                    $booking_date_format = date('d,M,Y', strtotime($get_bill->booking_date));
                    $result[] = array(
                        'booking_id' => $get_bill->id,
                        'booking_title' => $get_bill->booking_title,
                        'booking_location' => $get_bill->booking_location,
                        'booking_date' => $booking_date_format,
                        'booking_time' => '1',
                        'booking_payment_paid' => $user_detail->user_currency . ' ' . $user_detail->user_price,
                        'user_name' => $user_detail->name . ' ' . $user_detail->last_name,
                        'user_image' => $image_data
                    );
                }
                return json_encode(array('status' => "200", 'message' => 'my bills', 'result' => $result));
            }
        }
    }

    //My bill client section END
    //single bill client section START
    public function single_bill(Request $request) {
        $user_id = $request->input('user_id');
        $user_type = $request->input('user_type');
        $booking_id = $request->input('booking_id');
        if ($user_type == 'stylist') {
            $check_bill_count = DB::table('bookings')->where('id', $booking_id)->where('stylist_id', $user_id)->where('booking_status', '1')->count();
            if ($check_bill_count == '0') {
                $res['status'] = "201";
                $res['msg'] = "No booking made by you till now.";
                return json_encode($res);
            } else {
                $get_bills = DB::table('bookings')->where('id', $booking_id)->where('stylist_id', $user_id)->where('booking_status', '1')->get();

                foreach ($get_bills as $get_bill) {
                    $rescheduled_meeting_detail = DB::table('meeting_rescheduled')->where('booking_id', $get_bill->id)->orderBy('id', 'desc')->first();
                    $stylist_detail = DB::table('users')->where('id', $get_bill->user_id)->first();
                    if (isset($stylist_detail->image) && !empty($stylist_detail->image)) {
                        $image_data = url('../storage/files/') . '/' . $stylist_detail->image;
                    } else {
                        $image_data = '';
                    }
                    $booking_date_format = date('d,M,Y', strtotime($get_bill->booking_date));

                    if (isset($rescheduled_meeting_detail) && !empty($rescheduled_meeting_detail)) {

                        $rescheduled_time = $rescheduled_meeting_detail->time;
                        $rescheduled_started_meeting = $rescheduled_meeting_detail->meeting_scheduled_time;
                    } else {
                        $rescheduled_time = '';
                        $rescheduled_started_meeting = '';
                    }

                    $get_payments = DB::table('first_payment')->where('booking_id', $get_bill->id)->get();
                    if (count($get_payments) != '0') {
                        foreach ($get_payments as $get_payment) {
                            $all_payments[] = (array) $get_payment;
                        }
                        $total_amount_earned = 0;
                        foreach ($all_payments as $all_payment) {
                            $total_amount_earned += $all_payment['payment_amount'];
                        }
                        $stylist_earned = "$total_amount_earned";
                    } else {
                        $stylist_earned = "0";
                    }



                    $re_book = DB::table('meeting_rescheduled')->where('booking_id', $get_bill->id)->get();
                    if (count($re_book) != '0') {
                        foreach ($re_book as $re_books) {
                            $total_times[] = (array) $re_books;
                        }
                        $seconds = 0;
                        foreach ($total_times as $array_timess) {
                            list($hour, $minute, $second) = explode(':', $array_timess['time']);
                            $seconds += $hour * 3600;
                            $seconds += $minute * 60;
                            $seconds += $second;
                        }
                        $hours = floor($seconds / 3600);
                        $seconds -= $hours * 3600;
                        $minutes = floor($seconds / 60);
                        $seconds -= $minutes * 60;
                        if ($seconds < 9) {
                            $seconds = "0" . $seconds;
                        }
                        if ($minutes < 9) {
                            $minutes = "0" . $minutes;
                        }
                        if ($hours < 9) {
                            $hours = "0" . $hours + 1;
                        }
                        $total_worked_time = "{$hours}:{$minutes}:{$seconds}";
                    } else {
                        $total_worked_time = '1';
                    }

                    $get_bill_images = DB::table('bills')->where('booking_id', $get_bill->id)->first();
                    $get_bill_count_im = DB::table('bills')->where('booking_id', $get_bill->id)->count();
                    if ($get_bill_count_im != '0') {
                        if (isset($get_bill_images->bill_report1) && !empty($get_bill_images->bill_report1)) {
                            $image_data1 = url('../storage/bill_reports/') . '/' . $get_bill_images->bill_report1;
                        } else {
                            $image_data1 = '';
                        }

                        if (isset($get_bill_images->bill_report2) && !empty($get_bill_images->bill_report2)) {
                            $image_data2 = url('../storage/bill_reports/') . '/' . $get_bill_images->bill_report2;
                        } else {
                            $image_data2 = '';
                        }

                        if (isset($get_bill_images->bill_report3) && !empty($get_bill_images->bill_report3)) {
                            $image_data3 = url('../storage/bill_reports/') . '/' . $get_bill_images->bill_report3;
                        } else {
                            $image_data3 = '';
                        }

                        if (isset($get_bill_images->bill_report4) && !empty($get_bill_images->bill_report4)) {
                            $image_data4 = url('../storage/bill_reports/') . '/' . $get_bill_images->bill_report4;
                        } else {
                            $image_data4 = '';
                        }

//                        if (isset($get_bill_images->bill_report5) && !empty($get_bill_images->bill_report5)) {
//                            $image_data5 = url('../storage/bill_reports/') . '/' . $get_bill_images->bill_report5;
//                        } else {
//                            $image_data5 = '';
//                        }
                    } else {
                        $image_data1 = '';
                        $image_data2 = '';
                        $image_data3 = '';
                        $image_data4 = '';
                    }

                    $result[] = array(
                        'booking_id' => $get_bill->id,
                        'booking_title' => $get_bill->booking_title,
                        'booking_location' => $get_bill->booking_location,
                        'booking_date' => $booking_date_format,
                        'booking_time' => $total_worked_time,
                        'booking_payment_paid' => $stylist_detail->user_currency . ' ' . $stylist_earned,
                        'user_name' => $stylist_detail->name . ' ' . $stylist_detail->last_name,
                        'user_image' => $image_data,
                        'total_payment' => $stylist_detail->user_currency . ' ' . $stylist_detail->user_price,
                        'meeting_started_time' => $get_bill->meeting_started_time,
                        'meeting_status' => $get_bill->meeting_status,
                        'rescheduled_time' => $rescheduled_time,
                        'rescheduled_started_meeting' => $rescheduled_started_meeting,
                        'bill_image1' => $image_data1,
                        'bill_image2' => $image_data2,
                        'bill_image3' => $image_data3,
                        'bill_image4' => $image_data4,
                    );
                }
                return json_encode(array('status' => "200", 'message' => 'my bills', 'result' => $result));
            }
        } else {
            $check_bill_count = DB::table('bookings')->where('user_id', $user_id)->where('booking_status', '1')->count();
            if ($check_bill_count == '0') {
                $res['status'] = "201";
                $res['msg'] = "No booking made by your till now.";
                return json_encode($res);
            } else {
                $get_bills = DB::table('bookings')->where('id', $booking_id)->where('booking_status', '1')->get();
                foreach ($get_bills as $get_bill) {
                    $user_detail = DB::table('users')->where('id', $get_bill->stylist_id)->first();
                    $stylist_detail = DB::table('users')->where('id', $get_bill->user_id)->first();
                    if (isset($user_detail->image) && !empty($user_detail->image)) {
                        $image_data = url('../storage/files/') . '/' . $user_detail->image;
                    } else {
                        $image_data = '';
                    }
                    $booking_date_format = date('d,M,Y', strtotime($get_bill->booking_date));

                    if (isset($rescheduled_meeting_detail) && !empty($rescheduled_meeting_detail)) {

                        $rescheduled_time = $rescheduled_meeting_detail->time;
                        $rescheduled_started_meeting = $rescheduled_meeting_detail->meeting_scheduled_time;
                    } else {
                        $rescheduled_time = '';
                        $rescheduled_started_meeting = '';
                    }

                    $get_payments = DB::table('first_payment')->where('booking_id', $get_bill->id)->get();
                    if (count($get_payments) != '0') {
                        foreach ($get_payments as $get_payment) {
                            $all_payments[] = (array) $get_payment;
                        }
                        $total_amount_earned = 0;
                        foreach ($all_payments as $all_payment) {
                            $total_amount_earned += $all_payment['payment_amount'];
                        }
                        $stylist_earned = "$total_amount_earned";
                    } else {
                        $stylist_earned = "0";
                    }



                    $re_book = DB::table('meeting_rescheduled')->where('booking_id', $get_bill->id)->get();
                    if (count($re_book) != '0') {
                        foreach ($re_book as $re_books) {
                            $total_times[] = (array) $re_books;
                        }
                        $seconds = 0;
                        foreach ($total_times as $array_timess) {
                            list($hour, $minute, $second) = explode(':', $array_timess['time']);
                            $seconds += $hour * 3600;
                            $seconds += $minute * 60;
                            $seconds += $second;
                        }
                        $hours = floor($seconds / 3600);
                        $seconds -= $hours * 3600;
                        $minutes = floor($seconds / 60);
                        $seconds -= $minutes * 60;
                        if ($seconds < 9) {
                            $seconds = "0" . $seconds;
                        }
                        if ($minutes < 9) {
                            $minutes = "0" . $minutes;
                        }
                        if ($hours < 9) {
                            $hours = "0" . $hours + 1;
                        }
                        $total_worked_time = "{$hours}:{$minutes}:{$seconds}";
                    } else {
                        $total_worked_time = '1';
                    }

                    $get_bill_images = DB::table('bills')->where('booking_id', $get_bill->id)->first();
                    $get_bill_count_im = DB::table('bills')->where('booking_id', $get_bill->id)->count();
                    if ($get_bill_count_im != '0') {
                        if (isset($get_bill_images->bill_report1) && !empty($get_bill_images->bill_report1)) {
                            $image_data1 = url('../storage/bill_reports/') . '/' . $get_bill_images->bill_report1;
                        } else {
                            $image_data1 = '';
                        }

                        if (isset($get_bill_images->bill_report2) && !empty($get_bill_images->bill_report2)) {
                            $image_data2 = url('../storage/bill_reports/') . '/' . $get_bill_images->bill_report2;
                        } else {
                            $image_data2 = '';
                        }

                        if (isset($get_bill_images->bill_report3) && !empty($get_bill_images->bill_report3)) {
                            $image_data3 = url('../storage/bill_reports/') . '/' . $get_bill_images->bill_report3;
                        } else {
                            $image_data3 = '';
                        }

                        if (isset($get_bill_images->bill_report4) && !empty($get_bill_images->bill_report4)) {
                            $image_data4 = url('../storage/bill_reports/') . '/' . $get_bill_images->bill_report4;
                        } else {
                            $image_data4 = '';
                        }

//                        if (isset($get_bill_images->bill_report5) && !empty($get_bill_images->bill_report5)) {
//                            $image_data5 = url('../storage/bill_reports/') . '/' . $get_bill_images->bill_report5;
//                        } else {
//                            $image_data5 = '';
//                        }
                    } else {
                        $image_data1 = '';
                        $image_data2 = '';
                        $image_data3 = '';
                        $image_data4 = '';
                    }

                    $result[] = array(
                        'booking_id' => $get_bill->id,
                        'booking_title' => $get_bill->booking_title,
                        'booking_location' => $get_bill->booking_location,
                        'booking_date' => $booking_date_format,
                        'booking_time' => '1',
                        'booking_payment_paid' => $stylist_detail->user_currency . ' ' . $stylist_detail->user_price,
                        'user_name' => $user_detail->name . ' ' . $user_detail->last_name,
                        'user_image' => $image_data,
                        'total_payment' => $stylist_detail->user_currency . ' ' . $stylist_detail->user_price,
                        'meeting_started_time' => $get_bill->meeting_started_time,
                        'meeting_status' => $get_bill->meeting_status,
                        'rescheduled_time' => $rescheduled_time,
                        'rescheduled_started_meeting' => $rescheduled_started_meeting,
                        'bill_image1' => $image_data1,
                        'bill_image2' => $image_data2,
                        'bill_image3' => $image_data3,
                        'bill_image4' => $image_data4,
                    );
                }
                return json_encode(array('status' => "200", 'message' => 'all data', 'result' => $result));
            }
        }
    }

    //single bill client section END
    //Notification START
    public function multiple_notification($token, $notification, $data, $notification_type = "", $auth = "") {
        $fcmUrl = 'https://fcm.googleapis.com/fcm/send';
        $tokenList = $token;

        if (empty($auth) || !isset($auth)) {
            $auth = "AAAA7JZNVfI:APA91bFThKoVHXIiLOEgVL5t9PDIzZFlYZtMU-ow_DtmwvIHBs6xWQfZGxS93ZgKPTX_e9oYnCXLKPXqVX3o82U4fpe1cRF6IzPtjy6fRft_LSHc_xD6H_9RQuL22_1ZANgRTlMbnqYf";
        }
        //print_r($notification);
        //echo json_encode($notification);
        //die;
        $extraNotificationData = $data;

        $fcmNotification = [
//        'registration_ids' => $tokenList, //multple token array
            'to' => $token, //single token
            'notification' => $notification,
            'data' => $extraNotificationData
        ];
        //echo json_encode($fcmNotification);
        $headers = [
            'Authorization: key=' . $auth,
            'Content-Type: application/json'
        ];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $fcmUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fcmNotification));
        if (curl_error($ch)) {
            $error_msg = curl_error($ch);
        }
        $result = curl_exec($ch);
        curl_close($ch);

        if (!isset($error_msg)) {
            $error_msg = [];
        }

        $response = array("result" => $result, "error_msg" => $error_msg);
        //return $response;

        return true;
    }

    //Notification END
    //Scheduled meeting start or end START
    public function meeting_status(Request $request) {
        $booking_id = $request->input('booking_id');
        $scheduled_status = $request->input('scheduled_status');
        $scheduled_time = $request->input('time');
        $booking_status_count = DB::table('bookings')->where('id', $booking_id)->where('booking_status', '1')->count();
        $booking_detail = DB::table('bookings')->where('id', $booking_id)->where('booking_status', '1')->get();
        if ($booking_status_count == '0') {
            $res['status'] = "201";
            $res['msg'] = "First payment is still pending for this booking";
            return json_encode($res);
        } else {
            if ($scheduled_status == 'started') {
                date_default_timezone_set('Asia/Kolkata');
                $meeting_start_time = date('Y-m-d H:i:s');
                $meeting_count_check = DB::table('meeting_rescheduled')->where('booking_id', $booking_id)->count();
                if ($meeting_count_check == '0') {
                    DB::table('bookings')->where('id', $booking_id)->update([
                        'meeting_status' => 'started',
                        'meeting_started_time' => $meeting_start_time
                    ]);
                } else {
                    DB::table('bookings')->where('id', $booking_id)->update([
                        'meeting_status' => 'started'
                    ]);
                }

                $stylist_detailG = DB::table('users')->where('id', $booking_detail[0]->stylist_id)->first();
                $user_detailG = DB::table('users')->where('id', $booking_detail[0]->user_id)->first();
                $googleApiKey = 'AAAA7JZNVfI:APA91bFThKoVHXIiLOEgVL5t9PDIzZFlYZtMU-ow_DtmwvIHBs6xWQfZGxS93ZgKPTX_e9oYnCXLKPXqVX3o82U4fpe1cRF6IzPtjy6fRft_LSHc_xD6H_9RQuL22_1ZANgRTlMbnqYf';
                // print_r($data); die;
                $registrationIds = array($stylist_detailG->device_token);
                $message = "Congrats! your meeting has been started.";
                $title = "Meeting Started";
                $msg = array(
                    'message' => $message,
                    'title' => $title,
                    'user_id' => $booking_detail[0]->user_id,
                    'stylist_name' => $stylist_detailG->name . ' ' . $stylist_detailG->last_name,
                    "user_name" => $user_detailG->name . ' ' . $user_detailG->last_name,
                    'booking_id' => $booking_detail[0]->id,
                    'stylist_id' => $booking_detail[0]->stylist_id,
                    'vibrate' => 1,
                    'sound' => 1,
                    'type' => '113', //meeting started
                    'priority' => 'high',
                    'meeting_started_time' => $meeting_start_time,
                    'content_available' => true
                );
                if ($stylist_detailG->device_type == 'ios') {
                    $fields = array(
                        'registration_ids' => $registrationIds,
                        'notification' => $msg
                    );
                } else {
                    $fields = array(
                        'registration_ids' => $registrationIds,
                        'data' => $msg
                    );
                }

                $headers = array(
                    'Authorization: key=' . $googleApiKey,
                    'Content-Type: application/json'
                );
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
                $result = curl_exec($ch);


                curl_close($ch);
                DB::table('notifications')->insert([
                    'booking_id' => $booking_detail[0]->id,
                    'title' => $title,
                    'message' => $message,
                    'logged_id' => $stylist_detailG->id,
                    'user_id' => $user_detailG->id,
                    'notification_type' => '113',
                    'user_name' => $user_detailG->name . ' ' . $user_detailG->last_name
//                'default_message'=>'<p>Keyword/Occasion:' . $booking_detail[0]->booking_title . '</p><p>Date:' . $formated_date . '</p><p>Budget:' . $booking_detail[0]->user_budget . ' ' . $booking_detail[0]->user_currency . '</p>',
                ]);


                $googleApiKey = 'AAAA7JZNVfI:APA91bFThKoVHXIiLOEgVL5t9PDIzZFlYZtMU-ow_DtmwvIHBs6xWQfZGxS93ZgKPTX_e9oYnCXLKPXqVX3o82U4fpe1cRF6IzPtjy6fRft_LSHc_xD6H_9RQuL22_1ZANgRTlMbnqYf';
                // print_r($data); die;
                $registrationIds = array($user_detailG->device_token);
                $message = "Congrats! your meeting has been started.";
                $title = "Meeting Started";
                $msg = array(
                    'message' => $message,
                    'title' => $title,
                    'user_id' => $booking_detail[0]->user_id,
                    'stylist_name' => $stylist_detailG->name . ' ' . $stylist_detailG->last_name,
                    "user_name" => $user_detailG->name . ' ' . $user_detailG->last_name,
                    'booking_id' => $booking_detail[0]->id,
                    'stylist_id' => $booking_detail[0]->stylist_id,
                    'vibrate' => 1,
                    'sound' => 1,
                    'type' => '113', //meeting started
                    'priority' => 'high',
                    'meeting_started_time' => $meeting_start_time,
                    'content_available' => true
                );

                DB::table('notifications')->insert([
                    'booking_id' => $booking_detail[0]->id,
                    'title' => $title,
                    'message' => $message,
                    'logged_id' => $user_detailG->id,
                    'user_id' => $stylist_detailG->id,
                    'notification_type' => '113',
                    'user_name' => $user_detailG->name . ' ' . $user_detailG->last_name
//                'default_message'=>'<p>Keyword/Occasion:' . $booking_detail[0]->booking_title . '</p><p>Date:' . $formated_date . '</p><p>Budget:' . $booking_detail[0]->user_budget . ' ' . $booking_detail[0]->user_currency . '</p>',
                ]);

                if ($stylist_detailG->device_type == 'ios') {
                    $fields = array(
                        'registration_ids' => $registrationIds,
                        'notification' => $msg
                    );
                } else {
                    $fields = array(
                        'registration_ids' => $registrationIds,
                        'data' => $msg
                    );
                }

                $headers = array(
                    'Authorization: key=' . $googleApiKey,
                    'Content-Type: application/json'
                );
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
                $result = curl_exec($ch);


                curl_close($ch);

                $res['status'] = "200";
                $res['msg'] = "Congrats! meeting has been started.";
                return json_encode($res);
            } elseif ($scheduled_status == 'extended') {
                DB::table('bookings')->where('id', $booking_id)->update([
                    'meeting_status' => 'extended'
                ]);
                if ($scheduled_time != '00:00:00') {
                    date_default_timezone_set('Asia/Kolkata');
                    $meeting_start_time = date('Y-m-d H:i:s');
                    DB::table('meeting_rescheduled')->insert([
                        'booking_id' => $booking_id,
                        'time' => $scheduled_time,
                        'meeting_scheduled_time' => $meeting_start_time,
                    ]);
                }

                $stylist_detailG = DB::table('users')->where('id', $booking_detail[0]->stylist_id)->first();
                $user_detailG = DB::table('users')->where('id', $booking_detail[0]->user_id)->first();
                $googleApiKey = 'AAAA7JZNVfI:APA91bFThKoVHXIiLOEgVL5t9PDIzZFlYZtMU-ow_DtmwvIHBs6xWQfZGxS93ZgKPTX_e9oYnCXLKPXqVX3o82U4fpe1cRF6IzPtjy6fRft_LSHc_xD6H_9RQuL22_1ZANgRTlMbnqYf';
                // print_r($data); die;
                $registrationIds = array($stylist_detailG->device_token);
                $message = "Congrats! your meeting has been extended.";
                $title = "Meeting Extended";
                $msg = array(
                    'message' => $message,
                    'title' => $title,
                    'user_id' => $booking_detail[0]->user_id,
                    'stylist_name' => $stylist_detailG->name . ' ' . $stylist_detailG->last_name,
                    "user_name" => $user_detailG->name . ' ' . $user_detailG->last_name,
                    'booking_id' => $booking_detail[0]->id,
                    'stylist_id' => $booking_detail[0]->stylist_id,
                    'vibrate' => 1,
                    'sound' => 1,
                    'type' => '114', //meeting extended
                    'priority' => 'high',
                    'content_available' => true
                );

                DB::table('notifications')->insert([
                    'booking_id' => $booking_detail[0]->id,
                    'title' => $title,
                    'message' => $message,
                    'logged_id' => $stylist_detailG->id,
                    'user_id' => $user_detailG->id,
                    'notification_type' => '114',
                    'user_name' => $user_detailG->name . ' ' . $user_detailG->last_name
//                'default_message'=>'<p>Keyword/Occasion:' . $booking_detail[0]->booking_title . '</p><p>Date:' . $formated_date . '</p><p>Budget:' . $booking_detail[0]->user_budget . ' ' . $booking_detail[0]->user_currency . '</p>',
                ]);

                if ($stylist_detailG->device_type == 'ios') {
                    $fields = array(
                        'registration_ids' => $registrationIds,
                        'notification' => $msg
                    );
                } else {
                    $fields = array(
                        'registration_ids' => $registrationIds,
                        'data' => $msg
                    );
                }

                $headers = array(
                    'Authorization: key=' . $googleApiKey,
                    'Content-Type: application/json'
                );
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
                $result = curl_exec($ch);


                curl_close($ch);
                $res['status'] = "200";
                $res['msg'] = "Congrats! meeting has been extended successfully.";
                return json_encode($res);
            } elseif ($scheduled_status == 'ended') {
                DB::table('bookings')->where('id', $booking_id)->update([
                    'meeting_status' => 'ended'
                ]);

//                if($scheduled_time!='00:00:00'){
//                    DB::table('meeting_rescheduled')->insert([
//                        'booking_id' => $booking_id,
//                        'time' => $time
//                    ]);
//                }


                $stylist_detailG = DB::table('users')->where('id', $booking_detail[0]->stylist_id)->first();
                $user_detailG = DB::table('users')->where('id', $booking_detail[0]->user_id)->first();
                $googleApiKey = 'AAAA7JZNVfI:APA91bFThKoVHXIiLOEgVL5t9PDIzZFlYZtMU-ow_DtmwvIHBs6xWQfZGxS93ZgKPTX_e9oYnCXLKPXqVX3o82U4fpe1cRF6IzPtjy6fRft_LSHc_xD6H_9RQuL22_1ZANgRTlMbnqYf';
                // print_r($data); die;
                $registrationIds = array($stylist_detailG->device_token);
                $message = "Congrats! your meeting has been ended.";
                $title = "Meeting Ended";
                $msg = array(
                    'message' => $message,
                    'title' => $title,
                    'user_id' => $booking_detail[0]->user_id,
                    'stylist_name' => $stylist_detailG->name . ' ' . $stylist_detailG->last_name,
                    "user_name" => $user_detailG->name . ' ' . $user_detailG->last_name,
                    'booking_id' => $booking_detail[0]->id,
                    'stylist_id' => $booking_detail[0]->stylist_id,
                    'vibrate' => 1,
                    'sound' => 1,
                    'type' => '115', //meeting ended
                    'priority' => 'high',
                    'content_available' => true
                );

                DB::table('notifications')->insert([
                    'booking_id' => $booking_detail[0]->id,
                    'title' => $title,
                    'message' => $message,
                    'logged_id' => $stylist_detailG->id,
                    'user_id' => $user_detailG->id,
                    'notification_type' => '115',
                    'user_name' => $user_detailG->name . ' ' . $user_detailG->last_name
//                'default_message'=>'<p>Keyword/Occasion:' . $booking_detail[0]->booking_title . '</p><p>Date:' . $formated_date . '</p><p>Budget:' . $booking_detail[0]->user_budget . ' ' . $booking_detail[0]->user_currency . '</p>',
                ]);
                if ($stylist_detailG->device_type == 'ios') {
                    $fields = array(
                        'registration_ids' => $registrationIds,
                        'notification' => $msg
                    );
                } else {
                    $fields = array(
                        'registration_ids' => $registrationIds,
                        'data' => $msg
                    );
                }

                $headers = array(
                    'Authorization: key=' . $googleApiKey,
                    'Content-Type: application/json'
                );
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
                $result = curl_exec($ch);


                curl_close($ch);
                $res['status'] = "200";
                $res['msg'] = "Congrats! meeting has been extended successfully.";
                return json_encode($res);
            }
        }
    }

    //Scheduled meeting start or end END
    //Scheduled meeting extend START
    public function end_exteded_meeting(Request $request) {
        $booking_id = $request->input('booking_id');
        $time = $request->input('time');
        $booking_status_count = DB::table('bookings')->where('id', $booking_id)->where('booking_status', '1')->count();
        $booking_detail = DB::table('bookings')->where('id', $booking_id)->where('booking_status', '1')->get();
        if ($booking_status_count == '0') {
            $res['status'] = "201";
            $res['msg'] = "First payment is still pending for this booking";
            return json_encode($res);
        } else {

            DB::table('meeting_rescheduled')->insert([
                'booking_id' => $booking_id,
                'time' => $time
            ]);
            $stylist_detailG = DB::table('users')->where('id', $booking_detail[0]->stylist_id)->first();
            $user_detailG = DB::table('users')->where('id', $booking_detail[0]->user_id)->first();
            $googleApiKey = 'AAAA7JZNVfI:APA91bFThKoVHXIiLOEgVL5t9PDIzZFlYZtMU-ow_DtmwvIHBs6xWQfZGxS93ZgKPTX_e9oYnCXLKPXqVX3o82U4fpe1cRF6IzPtjy6fRft_LSHc_xD6H_9RQuL22_1ZANgRTlMbnqYf';
            // print_r($data); die;
            $registrationIds = array($stylist_detailG->device_token);
            $message = "Congrats! your extended meeting has been ended.";
            $title = "Extended Meeting Ended";
            $msg = array(
                'message' => $message,
                'title' => $title,
                'user_id' => $booking_detail[0]->user_id,
                'stylist_name' => $booking_detail[0]->user_name,
                "user_name" => $user_detailG->name . ' ' . $user_detailG->last_name,
                'booking_id' => $booking_detail[0]->id,
                'stylist_id' => $booking_detail[0]->stylist_id,
                'vibrate' => 1,
                'sound' => 1,
                'type' => '113', //meeting started
                'priority' => 'high',
                'content_available' => true
            );

            DB::table('notifications')->insert([
                'booking_id' => $booking_detail[0]->id,
                'title' => $title,
                'message' => $message,
                'logged_id' => $stylist_detailG->id,
                'user_id' => $user_detailG->id,
                'notification_type' => '113',
                'user_name' => $user_detailG->name . ' ' . $user_detailG->last_name
//                'default_message'=>'<p>Keyword/Occasion:' . $booking_detail[0]->booking_title . '</p><p>Date:' . $formated_date . '</p><p>Budget:' . $booking_detail[0]->user_budget . ' ' . $booking_detail[0]->user_currency . '</p>',
            ]);

            if ($stylist_detailG->device_type == 'ios') {
                $fields = array(
                    'registration_ids' => $registrationIds,
                    'notification' => $msg
                );
            } else {
                $fields = array(
                    'registration_ids' => $registrationIds,
                    'data' => $msg
                );
            }

            $headers = array(
                'Authorization: key=' . $googleApiKey,
                'Content-Type: application/json'
            );
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
            $result = curl_exec($ch);


            curl_close($ch);

            $res['status'] = "200";
            $res['msg'] = "Success! Your extened meeting has been ended.";
            return json_encode($res);
        }
    }

    //Scheduled meeting extend END
    //meetings START
    public function my_meetings(Request $request) {
        $user_id = $request->input('user_id');
        $user_type = $request->input('user_type');
        if ($user_type == 'stylist') {
            $check_bill_count = DB::table('bookings')->where('stylist_id', $user_id)->where('stylist_id', $user_id)->where('booking_status', '1')->count();
            if ($check_bill_count == '0') {
                $res['status'] = "201";
                $res['msg'] = "No meetings made by your till now.";
                return json_encode($res);
            } else {
                $get_bills = DB::table('bookings')->where('stylist_id', $user_id)->where('booking_status', '1')->orderBy('id', 'desc')->get();

                foreach ($get_bills as $get_bill) {
                    //print_r($get_bill->booking_date); die;
                    $stylist_detail = DB::table('users')->where('id', $get_bill->user_id)->first();
                    $rescheduled_meeting_detail = DB::table('meeting_rescheduled')->where('booking_id', $get_bill->id)->orderBy('id', 'desc')->first();
                    if (isset($stylist_detail->image) && !empty($stylist_detail->image)) {
                        $image_data = url('../storage/files/') . '/' . $stylist_detail->image;
                    } else {
                        $image_data = '';
                    }
                    if (isset($rescheduled_meeting_detail) && !empty($rescheduled_meeting_detail)) {

                        $rescheduled_time = $rescheduled_meeting_detail->time;
                        if ($rescheduled_time == '00:60:00') {
                            $rescheduled_started_meeting = $rescheduled_meeting_detail->meeting_scheduled_time;
                            $meeting_timestamp = strtotime($rescheduled_started_meeting) + 60 * 60;
                            $meeting_re_ended_time = date('H:i:s', $meeting_timestamp);
                            $rescheduled_flag = '60';
                        } else {
                            $rescheduled_started_meeting = $rescheduled_meeting_detail->meeting_scheduled_time;
                            $meeting_timestamp = strtotime($rescheduled_started_meeting) + 30 * 60;
                            $meeting_re_ended_time = date('H:i:s', $meeting_timestamp);
                            $rescheduled_flag = '30';
                        }
                    } else {
                        $rescheduled_time = '';
                        $rescheduled_started_meeting = '';
                        $meeting_re_ended_time = '';
                        $rescheduled_flag = '';
                    }
                    $booking_date_format = date('d,M,Y', strtotime($get_bill->booking_date));
                    $meeting_timestamp = strtotime($get_bill->meeting_started_time) + 60 * 60;
                    $meeting_ended_time = date('H:i:s', $meeting_timestamp);
                    $upload_bill_check = DB::table('bills')->where('booking_id', $get_bill->id)->count();
                    if ($upload_bill_check == '0') {
                        $bill_uploaded_status = '';
                    } else {
                        $bill_uploaded_status = 'uploaded';
                    }

                    $rating_check = DB::table('rating_to_user')->where('booking_id', $get_bill->id)->count();
                    if ($rating_check == '0') {
                        $rating_check_status = '';
                    } else {
                        $rating_check_status = 'rated';
                    }

                    $result[] = array(
                        'booking_id' => $get_bill->id,
                        'booking_title' => $get_bill->booking_title,
                        'booking_location' => $get_bill->booking_location,
                        'booking_date' => $booking_date_format,
                        'booking_time' => '1 hr',
                        'booking_payment_paid' => $stylist_detail->user_currency . ' ' . $stylist_detail->user_price,
                        'user_name' => $stylist_detail->name . ' ' . $stylist_detail->last_name,
                        'user_image' => $image_data,
                        'stylist_currency' => $get_bill->stylist_currency,
                        'stylist_price' => $get_bill->stylist_price,
                        'meeting_started_time' => $get_bill->meeting_started_time,
                        'meeting_ended_time' => $meeting_ended_time,
                        'meeting_status' => $get_bill->meeting_status,
                        'bill_uploaded_status' => $bill_uploaded_status,
                        'rating_check_status' => $rating_check_status,
                        'rescheduled_time' => $rescheduled_time,
                        'rescheduled_started_meeting' => $rescheduled_started_meeting,
                        'meeting_re_ended_time' => $meeting_re_ended_time,
                        'rescheduled_flag' => $rescheduled_flag,
                        'stylist_id' => $get_bill->stylist_id,
                    );
                }
                return json_encode(array('status' => "200", 'message' => 'all meetings', 'result' => $result));
            }
        } else {
            $check_bill_count = DB::table('bookings')->where('user_id', $user_id)->where('booking_status', '1')->count();
            if ($check_bill_count == '0') {
                $res['status'] = "201";
                $res['msg'] = "No meetings made by your till now.";
                return json_encode($res);
            } else {
                $get_bills = DB::table('bookings')->where('user_id', $user_id)->where('booking_status', '1')->orderBy('id', 'desc')->get();
                foreach ($get_bills as $get_bill) {
                    $rescheduled_meeting_detail = DB::table('meeting_rescheduled')->where('booking_id', $get_bill->id)->orderBy('id', 'desc')->first();
                    $user_detail = DB::table('users')->where('id', $get_bill->stylist_id)->first();
                    if (isset($user_detail->image) && !empty($user_detail->image)) {
                        $image_data = url('../storage/files/') . '/' . $user_detail->image;
                    } else {
                        $image_data = '';
                    }
                    $booking_date_format = date('d,M,Y', strtotime($get_bill->booking_date));
                    if (isset($rescheduled_meeting_detail) && !empty($rescheduled_meeting_detail)) {

                        $rescheduled_time = $rescheduled_meeting_detail->time;
                        if ($rescheduled_time == '00:60:00') {
                            $rescheduled_started_meeting = $rescheduled_meeting_detail->meeting_scheduled_time;
                            $meeting_timestamp = strtotime($rescheduled_started_meeting) + 60 * 60;
                            $meeting_re_ended_time = date('H:i:s', $meeting_timestamp);
                            $rescheduled_flag = '60';
                        } else {
                            $rescheduled_started_meeting = $rescheduled_meeting_detail->meeting_scheduled_time;
                            $meeting_timestamp = strtotime($rescheduled_started_meeting) + 30 * 60;
                            $meeting_re_ended_time = date('H:i:s', $meeting_timestamp);
                            $rescheduled_flag = '30';
                        }
                    } else {
                        $rescheduled_time = '';
                        $rescheduled_started_meeting = '';
                        $meeting_re_ended_time = '';
                        $rescheduled_flag = '';
                    }

                    $meeting_timestamp = strtotime($get_bill->meeting_started_time) + 60 * 60;
                    $meeting_ended_time = date('H:i:s', $meeting_timestamp);

                    $upload_bill_check = DB::table('bills')->where('booking_id', $get_bill->id)->count();
                    if ($upload_bill_check == '0') {
                        $bill_uploaded_status = '';
                    } else {
                        $bill_uploaded_status = 'uploaded';
                    }

                    $rating_check = DB::table('ratings')->where('booking_id', $get_bill->id)->count();
                    if ($rating_check == '0') {
                        $rating_check_status = '';
                    } else {
                        $rating_check_status = 'rated';
                    }

                    $result[] = array(
                        'booking_id' => $get_bill->id,
                        'booking_title' => $get_bill->booking_title,
                        'booking_location' => $get_bill->booking_location,
                        'booking_date' => $booking_date_format,
                        'booking_time' => '1',
                        'booking_payment_paid' => $user_detail->user_currency . ' ' . $user_detail->user_price,
                        'user_name' => $user_detail->name . ' ' . $user_detail->last_name,
                        'user_image' => $image_data,
                        'stylist_currency' => $get_bill->stylist_currency,
                        'stylist_price' => $get_bill->stylist_price,
                        'meeting_started_time' => $get_bill->meeting_started_time,
                        'meeting_ended_time' => $meeting_ended_time,
                        'meeting_status' => $get_bill->meeting_status,
                        'bill_uploaded_status' => $bill_uploaded_status,
                        'rating_check_status' => $rating_check_status,
                        'rescheduled_time' => $rescheduled_time,
                        'rescheduled_started_meeting' => $rescheduled_started_meeting,
                        'meeting_re_ended_time' => $meeting_re_ended_time,
                        'rescheduled_flag' => $rescheduled_flag,
                        'stylist_id' => $get_bill->stylist_id,
                    );
                }
                return json_encode(array('status' => "200", 'message' => 'all meetings', 'result' => $result));
            }
        }
    }

    //meetings END
    //upload bills START
    public function upload_bills(Request $request) {
        $booking_id = $request->input('booking_id');
//        $stylist_id = $request->input('stylist_id');
//        $client_id = $request->input('client_id');

        if ($booking_id == '') {
            $res['status'] = "201";
            $res['msg'] = "Please fill required fields";
            return json_encode($res);
        } else {
            if ($file = $request->hasFile('bill_report1')) {
                $file = $request->file('bill_report1');
                $imageType = $file->getClientmimeType();
                // print_r($imageType); die;
                $rules = array(
                    'image' => 'mimes:jpeg,jpg,png',
                );

                $validator = Validator::make($request->all(), $rules);

                if ($validator->fails()) {
                    $res['status'] = "201";
                    $res['msg'] = "Image format must be jpeg,jpg,png";
                    return json_encode($res);
                } else {
                    $fileName = $file->getClientOriginalName();
                    $fileNameUnique = time() . '_' . $fileName;
                    $destinationPath = storage_path() . '/bill_reports/';
                    $file->move($destinationPath, $fileNameUnique);
                }
                $imageData1 = $fileNameUnique;
            } else {
                $imageData1 = '';
            }
            if ($file = $request->hasFile('bill_report2')) {
                $file = $request->file('bill_report2');
                $imageType = $file->getClientmimeType();
                // print_r($imageType); die;
                $rules = array(
                    'image' => 'mimes:jpeg,jpg,png',
                );

                $validator = Validator::make($request->all(), $rules);

                if ($validator->fails()) {
                    $res['status'] = "201";
                    $res['msg'] = "Image format must be jpeg,jpg,png";
                    return json_encode($res);
                } else {
                    $fileName = $file->getClientOriginalName();
                    $fileNameUnique = time() . '_' . $fileName;
                    $destinationPath = storage_path() . '/bill_reports/';
                    $file->move($destinationPath, $fileNameUnique);
                }
                $imageData2 = $fileNameUnique;
            } else {
                $imageData2 = '';
            }
            if ($file = $request->hasFile('bill_report3')) {
                $file = $request->file('bill_report3');
                $imageType = $file->getClientmimeType();
                // print_r($imageType); die;
                $rules = array(
                    'image' => 'mimes:jpeg,jpg,png',
                );

                $validator = Validator::make($request->all(), $rules);

                if ($validator->fails()) {
                    $res['status'] = "201";
                    $res['msg'] = "Image format must be jpeg,jpg,png";
                    return json_encode($res);
                } else {
                    $fileName = $file->getClientOriginalName();
                    $fileNameUnique = time() . '_' . $fileName;
                    $destinationPath = storage_path() . '/bill_reports/';
                    $file->move($destinationPath, $fileNameUnique);
                }
                $imageData3 = $fileNameUnique;
            } else {
                $imageData3 = '';
            }
            if ($file = $request->hasFile('bill_report4')) {
                $file = $request->file('bill_report4');
                $imageType = $file->getClientmimeType();
                // print_r($imageType); die;
                $rules = array(
                    'image' => 'mimes:jpeg,jpg,png',
                );

                $validator = Validator::make($request->all(), $rules);

                if ($validator->fails()) {
                    $res['status'] = "201";
                    $res['msg'] = "Image format must be jpeg,jpg,png";
                    return json_encode($res);
                } else {
                    $fileName = $file->getClientOriginalName();
                    $fileNameUnique = time() . '_' . $fileName;
                    $destinationPath = storage_path() . '/bill_reports/';
                    $file->move($destinationPath, $fileNameUnique);
                }
                $imageData4 = $fileNameUnique;
            } else {
                $imageData4 = '';
            }
            if ($file = $request->hasFile('bill_report5')) {
                $file = $request->file('bill_report5');
                $imageType = $file->getClientmimeType();
                // print_r($imageType); die;
                $rules = array(
                    'image' => 'mimes:jpeg,jpg,png',
                );

                $validator = Validator::make($request->all(), $rules);

                if ($validator->fails()) {
                    $res['status'] = "201";
                    $res['msg'] = "Image format must be jpeg,jpg,png";
                    return json_encode($res);
                } else {
                    $fileName = $file->getClientOriginalName();
                    $fileNameUnique = time() . '_' . $fileName;
                    $destinationPath = storage_path() . '/bill_reports/';
                    $file->move($destinationPath, $fileNameUnique);
                }
                $imageData5 = $fileNameUnique;
            } else {
                $imageData5 = '';
            }

            DB::table('bills')->insert([
//                'stylist_id' => $stylist_id,
                'booking_id' => $booking_id,
//                'client_id' => $client_id,
                'bill_report1' => $imageData1,
                'bill_report2' => $imageData2,
                'bill_report3' => $imageData3,
                'bill_report4' => $imageData4,
                'bill_report5' => $imageData5,
            ]);
            $res['status'] = "200";
            $res['msg'] = "Your bills has been uploaded successfully.";
            return json_encode($res);
        }
    }

    //upload bills END
    //Bill listing START
    public function bills_listing(Request $request) {
        $booking_id = $request->input('booking_id');
        $get_bills_count = DB::table('bills')->where('booking_id', $booking_id)->count();
        $get_bills = DB::table('bills')->where('booking_id', $booking_id)->get();
        if ($get_bills_count == '0') {
            $res['status'] = "201";
            $res['msg'] = "No bill uploaded till now";
            return json_encode($res);
        } else {
            foreach ($get_bills as $get_bill) {
                if (isset($get_bill->bill_report1) && !empty($get_bill->bill_report1)) {
                    $image_data1 = url('../storage/bill_reports/') . '/' . $get_bill->bill_report1;
                } else {
                    $image_data1 = '';
                }

                if (isset($get_bill->bill_report2) && !empty($get_bill->bill_report2)) {
                    $image_data2 = url('../storage/bill_reports/') . '/' . $get_bill->bill_report2;
                } else {
                    $image_data2 = '';
                }

                if (isset($get_bill->bill_report3) && !empty($get_bill->bill_report3)) {
                    $image_data3 = url('../storage/bill_reports/') . '/' . $get_bill->bill_report3;
                } else {
                    $image_data3 = '';
                }

                if (isset($get_bill->bill_report4) && !empty($get_bill->bill_report4)) {
                    $image_data4 = url('../storage/bill_reports/') . '/' . $get_bill->bill_report4;
                } else {
                    $image_data4 = '';
                }

                if (isset($get_bill->bill_report5) && !empty($get_bill->bill_report5)) {
                    $image_data5 = url('../storage/bill_reports/') . '/' . $get_bill->bill_report5;
                } else {
                    $image_data5 = '';
                }

                $result[] = array(
                    'image_data1' => $image_data1,
                    'image_data2' => $image_data2,
                    'image_data3' => $image_data3,
                    'image_data4' => $image_data4,
                    'image_data5' => $image_data5,
                );
            }
            return json_encode(array('status' => "200", 'message' => 'my bill detail', 'result' => $result));
        }
    }

    //Bill listing END
    //Payment history START
    public function payment_history(Request $request) {
        $user_id = $request->input('user_id');
        $user_type = $request->input('user_type');
        if ($user_type == 'stylist') {
            $allData = DB::table('bookings')
                    ->leftjoin('first_payment', 'first_payment.booking_id', '=', 'bookings.id')
                    ->leftjoin('users', 'users.id', '=', 'bookings.user_id')
                    ->select('bookings.*', 'first_payment.payment_id', 'first_payment.payment_amount as paid', 'first_payment.payment_currency as paid_currency', 'users.name as stylist_name')
                    ->where('bookings.stylist_id', '=', $user_id)
                    ->get();

            if (count($allData) != '0') {
                $total = 0;
                foreach ($allData as $allDatas) {
                    $result[] = array(
                        'booking_id' => $allDatas->id,
                        'booking_user_name' => $allDatas->stylist_name,
                        'booking_title' => $allDatas->booking_title,
                        'booking_date' => $allDatas->booking_date,
                        'booking_time' => $allDatas->booking_time,
                        'payment_id' => $allDatas->payment_id,
                        'payment_paid' => $allDatas->paid,
                        'paid_currency' => $allDatas->paid_currency,
                    );
                    $total += $allDatas->paid;
                }
                return json_encode(array('status' => "200", 'message' => 'my payment history', 'result' => $result, 'total' => $total));
            } else {
                $res['status'] = "201";
                $res['msg'] = "No payment history.";
                return json_encode($res);
            }
        } else {
            $allData = DB::table('bookings')
                    ->leftjoin('first_payment', 'first_payment.booking_id', '=', 'bookings.id')
                    ->leftjoin('users', 'users.id', '=', 'bookings.stylist_id')
                    ->select('bookings.*', 'first_payment.payment_id', 'first_payment.payment_amount as paid', 'first_payment.payment_currency as paid_currency', 'users.name as stylist_name')
                    ->where('bookings.user_id', '=', $user_id)
                    ->get();
            if (count($allData) != '0') {
                $total = 0;
                foreach ($allData as $allDatas) {
                    $result[] = array(
                        'booking_id' => $allDatas->id,
                        'booking_user_name' => $allDatas->stylist_name,
                        'booking_title' => $allDatas->booking_title,
                        'booking_date' => $allDatas->booking_date,
                        'booking_time' => $allDatas->booking_time,
                        'payment_id' => $allDatas->payment_id,
                        'payment_paid' => $allDatas->paid,
                        'paid_currency' => $allDatas->paid_currency,
                    );
                    $total += $allDatas->paid;
                }
                return json_encode(array('status' => "200", 'message' => 'my payment history', 'result' => $result, 'total' => $total));
            } else {
                $res['status'] = "201";
                $res['msg'] = "No payment history.";
                return json_encode($res);
            }
        }
    }

    //Payment history END
    //Clear recent history START
    public function clear_history(Request $request) {
        $recent_id = $request->input('recent_id');
        $delete_type = $request->input('delete_type');
        $user_id = $request->input('user_id');
        $delete = $request->input('delete');

        //Single delete data of history
        if ($delete_type == '' && $delete != 'all') {
            DB::table('user_text_searches')->where('id', $recent_id)->delete();
            $res['status'] = "200";
            $res['msg'] = "Recent data has been cleared";
            return json_encode($res);
        }
        if ($delete_type == 'style' && $delete != 'all') {
            DB::table('user_category_searches')->where('id', $recent_id)->delete();
            $res['status'] = "200";
            $res['msg'] = "Recent data has been cleared";
            return json_encode($res);
        }
        if ($delete_type == 'stylist' && $delete != 'all') {
            DB::table('user_stylist_searches')->where('id', $recent_id)->delete();
            $res['status'] = "200";
            $res['msg'] = "Recent data has been cleared";
            return json_encode($res);
        }
        //all delete data of history
        if ($delete == 'all' && $delete_type == '') {

            $arr1 = DB::table('user_text_searches')->where('user_id', $user_id)->get();
            $arr2 = DB::table('user_category_searches')->where('user_id', $user_id)->get();
            $arr3 = DB::table('user_stylist_searches')->where('user_id', $user_id)->get();
            if (count($arr1) != 0) {
                foreach ($arr1 as $aar1a) {
                    $arra1Data[] = (array) $aar1a;
                }
            } else {
                $arra1Data[] = array();
            }
            if (count($arr2) != 0) {
                foreach ($arr2 as $key2 => $aar2a) {
                    $arra2Data[] = (array) $aar2a;
                }
            } else {
                $arra2Data = array();
            }

            if (count($arr3) != 0) {
                foreach ($arr3 as $key3 => $aar3a) {
                    $arra3Data[] = (array) $aar3a;
                }
            } else {
                $arra3Data = array();
            }


            foreach ($arra1Data as $key1 => $arra1Datas) {
                $arra1Data[$key1]['type'] = '';
            }

            foreach ($arra2Data as $key2 => $arra2Datas) {
                $arra2Data[$key2]['type'] = 'style';
            }
            foreach ($arra3Data as $key3 => $arra3Datas) {
                $arra3Data[$key3]['type'] = 'stylist';
            }

            $final_array = array_merge($arra1Data, $arra2Data, $arra3Data);
//            print_r($final_array); die;
            foreach ($final_array as $arr_keys => $final_arrays) {
                if ($arr_keys == '')
                    unset($final_array[$arr_keys]);
            }
//            print_r($final_array); die;
            foreach ($final_array as $final_arrays) {
                if ($final_arrays['type'] == '') {
                    DB::table('user_text_searches')->where('id', $final_arrays['id'])->delete();
                }
                if ($final_arrays['type'] == 'style') {
                    DB::table('user_category_searches')->where('id', $final_arrays['id'])->delete();
                }
                if ($final_arrays['type'] == 'stylist') {
                    DB::table('user_stylist_searches')->where('id', $final_arrays['id'])->delete();
                }
            }
            $res['status'] = "200";
            $res['msg'] = "Recent data has been cleared";
            return json_encode($res);
        }
    }

    //Clear recent history END
    //Stylist banner upload START
    public function stylist_banner(Request $request) {
        $user_id = $request->input('user_id');
        if ($user_id == '') {
            $res['status'] = "201";
            $res['msg'] = "Please fill required fields";
            return json_encode($res);
        } else {
            if ($request->input('banner_id') == '') {
                if ($file = $request->hasFile('image1')) {
                    $file = $request->file('image1');
                    $imageType = $file->getClientmimeType();
                    // print_r($imageType); die;
                    $rules = array(
                        'image' => 'mimes:jpeg,jpg,png',
                    );

                    $validator = Validator::make($request->all(), $rules);

                    if ($validator->fails()) {
                        $res['status'] = "201";
                        $res['msg'] = "Image format must be jpeg,jpg,png";
                        return json_encode($res);
                    } else {
                        $fileName = $file->getClientOriginalName();
                        $fileNameUnique = time() . '_' . $fileName;
                        $destinationPath = storage_path() . '/stylist_banner/';
                        $file->move($destinationPath, $fileNameUnique);
                    }
                    $imageData1 = $fileNameUnique;
                } else {
                    $imageData1 = '';
                }
                if ($file = $request->hasFile('image2')) {
                    $file = $request->file('image2');
                    $imageType = $file->getClientmimeType();
                    // print_r($imageType); die;
                    $rules = array(
                        'image' => 'mimes:jpeg,jpg,png',
                    );

                    $validator = Validator::make($request->all(), $rules);

                    if ($validator->fails()) {
                        $res['status'] = "201";
                        $res['msg'] = "Image format must be jpeg,jpg,png";
                        return json_encode($res);
                    } else {
                        $fileName = $file->getClientOriginalName();
                        $fileNameUnique = time() . '_' . $fileName;
                        $destinationPath = storage_path() . '/stylist_banner/';
                        $file->move($destinationPath, $fileNameUnique);
                    }
                    $imageData2 = $fileNameUnique;
                } else {
                    $imageData2 = '';
                }
                if ($file = $request->hasFile('image3')) {
                    $file = $request->file('image3');
                    $imageType = $file->getClientmimeType();
                    // print_r($imageType); die;
                    $rules = array(
                        'image' => 'mimes:jpeg,jpg,png',
                    );

                    $validator = Validator::make($request->all(), $rules);

                    if ($validator->fails()) {
                        $res['status'] = "201";
                        $res['msg'] = "Image format must be jpeg,jpg,png";
                        return json_encode($res);
                    } else {
                        $fileName = $file->getClientOriginalName();
                        $fileNameUnique = time() . '_' . $fileName;
                        $destinationPath = storage_path() . '/stylist_banner/';
                        $file->move($destinationPath, $fileNameUnique);
                    }
                    $imageData3 = $fileNameUnique;
                } else {
                    $imageData3 = '';
                }
                if ($file = $request->hasFile('image4')) {
                    $file = $request->file('image4');
                    $imageType = $file->getClientmimeType();
                    // print_r($imageType); die;
                    $rules = array(
                        'image' => 'mimes:jpeg,jpg,png',
                    );

                    $validator = Validator::make($request->all(), $rules);

                    if ($validator->fails()) {
                        $res['status'] = "201";
                        $res['msg'] = "Image format must be jpeg,jpg,png";
                        return json_encode($res);
                    } else {
                        $fileName = $file->getClientOriginalName();
                        $fileNameUnique = time() . '_' . $fileName;
                        $destinationPath = storage_path() . '/stylist_banner/';
                        $file->move($destinationPath, $fileNameUnique);
                    }
                    $imageData4 = $fileNameUnique;
                } else {
                    $imageData4 = '';
                }

                DB::table('stylist_banners')->insert([
                    'user_id' => $user_id,
                    'image1' => $imageData1,
                    'image2' => $imageData2,
                    'image3' => $imageData3,
                    'image4' => $imageData4,
                ]);
            } else {
                if ($file = $request->hasFile('image1')) {
                    $file = $request->file('image1');
                    $imageType = $file->getClientmimeType();
                    // print_r($imageType); die;
                    $rules = array(
                        'image' => 'mimes:jpeg,jpg,png',
                    );

                    $validator = Validator::make($request->all(), $rules);

                    if ($validator->fails()) {
                        $res['status'] = "201";
                        $res['msg'] = "Image format must be jpeg,jpg,png";
                        return json_encode($res);
                    } else {
                        $fileName = $file->getClientOriginalName();
                        $fileNameUnique = time() . '_' . $fileName;
                        $destinationPath = storage_path() . '/stylist_banner/';
                        $file->move($destinationPath, $fileNameUnique);
                    }
                    $imageData1 = $fileNameUnique;
                } else {
                    $imageData1 = '';
                }
                if ($file = $request->hasFile('image2')) {
                    $file = $request->file('image2');
                    $imageType = $file->getClientmimeType();
                    // print_r($imageType); die;
                    $rules = array(
                        'image' => 'mimes:jpeg,jpg,png',
                    );

                    $validator = Validator::make($request->all(), $rules);

                    if ($validator->fails()) {
                        $res['status'] = "201";
                        $res['msg'] = "Image format must be jpeg,jpg,png";
                        return json_encode($res);
                    } else {
                        $fileName = $file->getClientOriginalName();
                        $fileNameUnique = time() . '_' . $fileName;
                        $destinationPath = storage_path() . '/stylist_banner/';
                        $file->move($destinationPath, $fileNameUnique);
                    }
                    $imageData2 = $fileNameUnique;
                } else {
                    $imageData2 = '';
                }
                if ($file = $request->hasFile('image3')) {
                    $file = $request->file('image3');
                    $imageType = $file->getClientmimeType();
                    // print_r($imageType); die;
                    $rules = array(
                        'image' => 'mimes:jpeg,jpg,png',
                    );

                    $validator = Validator::make($request->all(), $rules);

                    if ($validator->fails()) {
                        $res['status'] = "201";
                        $res['msg'] = "Image format must be jpeg,jpg,png";
                        return json_encode($res);
                    } else {
                        $fileName = $file->getClientOriginalName();
                        $fileNameUnique = time() . '_' . $fileName;
                        $destinationPath = storage_path() . '/stylist_banner/';
                        $file->move($destinationPath, $fileNameUnique);
                    }
                    $imageData3 = $fileNameUnique;
                } else {
                    $imageData3 = '';
                }
                if ($file = $request->hasFile('image4')) {
                    $file = $request->file('image4');
                    $imageType = $file->getClientmimeType();
                    // print_r($imageType); die;
                    $rules = array(
                        'image' => 'mimes:jpeg,jpg,png',
                    );

                    $validator = Validator::make($request->all(), $rules);

                    if ($validator->fails()) {
                        $res['status'] = "201";
                        $res['msg'] = "Image format must be jpeg,jpg,png";
                        return json_encode($res);
                    } else {
                        $fileName = $file->getClientOriginalName();
                        $fileNameUnique = time() . '_' . $fileName;
                        $destinationPath = storage_path() . '/stylist_banner/';
                        $file->move($destinationPath, $fileNameUnique);
                    }
                    $imageData4 = $fileNameUnique;
                } else {
                    $imageData4 = '';
                }

                DB::table('stylist_banners')->where('id', $request->input('banner_id'))->update([
                    'user_id' => $user_id,
                    'image1' => $imageData1,
                    'image2' => $imageData2,
                    'image3' => $imageData3,
                    'image4' => $imageData4,
                ]);
            }

            $res['status'] = "200";
            $res['msg'] = "Your banners has been uploaded successfully.";
            return json_encode($res);
        }
    }

    //Stylist banner upload END
    //How to earn START
    public function page_earn(Request $request) {
        $page_id = $_GET['id'];
        $earn_page = DB::table('pages')->where('id', $page_id)->first();
        $result[] = array(
            'title' => $earn_page->title,
            'description' => $earn_page->description,
        );
        return json_encode(array('status' => "200", 'message' => 'data', 'result' => $result));
    }

    //How to earn END
    //Notification list START
    public function notifications(Request $request) {
        $user_id = $request->input('user_id');
        $get_data = DB::table('notifications')->where('logged_id', $user_id)->get();
        if (count($get_data) != '0') {
            foreach ($get_data as $get_datas) {
                $result[] = array(
                    'booking_id' => $get_datas->booking_id,
                    'title' => $get_datas->title,
                    'message' => $get_datas->message,
                    'user_id' => $get_datas->user_id,
                    'logged_id' => $get_datas->logged_id,
                    'notification_type' => $get_datas->notification_type,
                    'user_name' => $get_datas->user_name,
                    'default_message' => $get_datas->default_message,
                    'notification_date' => $get_datas->created_at,
                );
            }
            return json_encode(array('status' => "200", 'message' => 'notification list', 'data' => $result));
        } else {
            $res['status'] = "201";
            $res['msg'] = "No notification till now!";
            return json_encode($res);
        }
    }

    //Notification list END
    //Get calendar booking START
    public function calandar_data(Request $request) {
        $user_id = $request->input('user_id');
        $user_type = $request->input('user_type');
        if ($user_type == 'stylist') {
            $check_bill_count = DB::table('bookings')->where('stylist_id', $user_id)->where('stylist_id', $user_id)->where('booking_status', '1')->count();
            if ($check_bill_count == '0') {
                $res['status'] = "201";
                $res['msg'] = "No meetings made by your till now.";
                return json_encode($res);
            } else {
                $get_bills = DB::table('bookings')->where('stylist_id', $user_id)->where('booking_status', '1')->orderBy('id', 'desc')->get();

                foreach ($get_bills as $get_bill) {
                    //print_r($get_bill->booking_date); die;
                    $stylist_detail = DB::table('users')->where('id', $get_bill->stylist_id)->first();
                    $rescheduled_meeting_detail = DB::table('meeting_rescheduled')->where('booking_id', $get_bill->id)->orderBy('id', 'desc')->first();
                    if (isset($stylist_detail->image) && !empty($stylist_detail->image)) {
                        $image_data = url('../storage/files/') . '/' . $stylist_detail->image;
                    } else {
                        $image_data = '';
                    }
                    if (isset($rescheduled_meeting_detail) && !empty($rescheduled_meeting_detail)) {

                        $rescheduled_time = $rescheduled_meeting_detail->time;
                        if ($rescheduled_time == '00:60:00') {
                            $rescheduled_started_meeting = $rescheduled_meeting_detail->meeting_scheduled_time;
                            $meeting_timestamp = strtotime($rescheduled_started_meeting) + 60 * 60;
                            $meeting_re_ended_time = date('H:i:s', $meeting_timestamp);
                            $rescheduled_flag = '60';
                        } else {
                            $rescheduled_started_meeting = $rescheduled_meeting_detail->meeting_scheduled_time;
                            $meeting_timestamp = strtotime($rescheduled_started_meeting) + 30 * 60;
                            $meeting_re_ended_time = date('H:i:s', $meeting_timestamp);
                            $rescheduled_flag = '30';
                        }
                    } else {
                        $rescheduled_time = '';
                        $rescheduled_started_meeting = '';
                        $meeting_re_ended_time = '';
                        $rescheduled_flag = '';
                    }
                    $booking_date_format = date('d,M,Y', strtotime($get_bill->booking_date));
                    $meeting_timestamp = strtotime($get_bill->meeting_started_time) + 60 * 60;
                    $meeting_ended_time = date('H:i:s', $meeting_timestamp);
                    $upload_bill_check = DB::table('bills')->where('booking_id', $get_bill->id)->count();
                    if ($upload_bill_check == '0') {
                        $bill_uploaded_status = '';
                    } else {
                        $bill_uploaded_status = 'uploaded';
                    }

                    $rating_check = DB::table('rating_to_user')->where('booking_id', $get_bill->id)->count();
                    if ($rating_check == '0') {
                        $rating_check_status = '';
                    } else {
                        $rating_check_status = 'rated';
                    }
//                    echo $get_bill->meeting_started_time; die;
                    $booking_date_format12 = date('Y-m-d', strtotime($get_bill->booking_date)) . ' ' . $get_bill->booking_time;
                    $start_date_full_format = date('Y-m-d h:i:s', strtotime($booking_date_format12));
                    $b_start_date_time = $booking_date_format . ' ' . $get_bill->meeting_started_time;
                    $start_date = date('D', strtotime($start_date_full_format));
                    $start_month = date('m/d', strtotime($start_date_full_format));
                    $start_time = date('D, M d Y', strtotime($start_date_full_format));
                    $event_start_time = date('D, M d Y h:i A', strtotime($start_date_full_format));

                    $result[] = array(
                        'booking_id' => $get_bill->id,
                        'booking_title' => $get_bill->booking_title,
                        'booking_location' => $get_bill->booking_location,
                        'booking_started_day' => $start_date,
                        'booking_started_month' => $start_month,
                        'booking_started_time' => $start_time,
                        'booking_started_date_time' => $event_start_time,
                        'booking_time' => '1 hr',
                        'booking_payment_paid' => $stylist_detail->user_currency . ' ' . $stylist_detail->user_price,
                        'user_name' => $stylist_detail->name . ' ' . $stylist_detail->last_name,
                        'user_image' => $image_data,
                        'stylist_currency' => $get_bill->stylist_currency,
                        'stylist_price' => $get_bill->stylist_price,
                        'meeting_started_time' => $get_bill->meeting_started_time,
                        'meeting_ended_time' => $meeting_ended_time,
                        'meeting_status' => $get_bill->meeting_status,
                        'bill_uploaded_status' => $bill_uploaded_status,
                        'rating_check_status' => $rating_check_status,
                        'rescheduled_time' => $rescheduled_time,
                        'rescheduled_started_meeting' => $rescheduled_started_meeting,
                        'meeting_re_ended_time' => $meeting_re_ended_time,
                        'rescheduled_flag' => $rescheduled_flag,
                        'stylist_id' => $get_bill->stylist_id,
                    );
                }
                return json_encode(array('status' => "200", 'message' => 'all meetings', 'result' => $result));
            }
        } else {
            $check_bill_count = DB::table('bookings')->where('user_id', $user_id)->where('booking_status', '1')->where('meeting_status', 'not started')->count();
            if ($check_bill_count == '0') {
                $res['status'] = "201";
                $res['msg'] = "No meetings made by your till now.";
                return json_encode($res);
            } else {
                $get_bills = DB::table('bookings')->where('user_id', $user_id)->where('booking_status', '1')->orderBy('id', 'desc')->where('meeting_status', 'not started')->get();
                foreach ($get_bills as $get_bill) {
                    $rescheduled_meeting_detail = DB::table('meeting_rescheduled')->where('booking_id', $get_bill->id)->orderBy('id', 'desc')->first();
                    $user_detail = DB::table('users')->where('id', $get_bill->stylist_id)->first();
                    if (isset($user_detail->image) && !empty($user_detail->image)) {
                        $image_data = url('../storage/files/') . '/' . $user_detail->image;
                    } else {
                        $image_data = '';
                    }
                    $booking_date_format = date('d,M,Y', strtotime($get_bill->booking_date));
                    if (isset($rescheduled_meeting_detail) && !empty($rescheduled_meeting_detail)) {

                        $rescheduled_time = $rescheduled_meeting_detail->time;
                        if ($rescheduled_time == '00:60:00') {
                            $rescheduled_started_meeting = $rescheduled_meeting_detail->meeting_scheduled_time;
                            $meeting_timestamp = strtotime($rescheduled_started_meeting) + 60 * 60;
                            $meeting_re_ended_time = date('H:i:s', $meeting_timestamp);
                            $rescheduled_flag = '60';
                        } else {
                            $rescheduled_started_meeting = $rescheduled_meeting_detail->meeting_scheduled_time;
                            $meeting_timestamp = strtotime($rescheduled_started_meeting) + 30 * 60;
                            $meeting_re_ended_time = date('H:i:s', $meeting_timestamp);
                            $rescheduled_flag = '30';
                        }
                    } else {
                        $rescheduled_time = '';
                        $rescheduled_started_meeting = '';
                        $meeting_re_ended_time = '';
                        $rescheduled_flag = '';
                    }

                    $meeting_timestamp = strtotime($get_bill->meeting_started_time) + 60 * 60;
                    $meeting_ended_time = date('H:i:s', $meeting_timestamp);

                    $upload_bill_check = DB::table('bills')->where('booking_id', $get_bill->id)->count();
                    if ($upload_bill_check == '0') {
                        $bill_uploaded_status = '';
                    } else {
                        $bill_uploaded_status = 'uploaded';
                    }

                    $rating_check = DB::table('ratings')->where('booking_id', $get_bill->id)->count();
                    if ($rating_check == '0') {
                        $rating_check_status = '';
                    } else {
                        $rating_check_status = 'rated';
                    }
                    $booking_date_format12 = date('Y-m-d', strtotime($get_bill->booking_date)) . ' ' . $get_bill->booking_time;
                    $start_date_full_format = date('Y-m-d h:i:s', strtotime($booking_date_format12));
                    $b_start_date_time = $booking_date_format . ' ' . $get_bill->meeting_started_time;
                    $start_date = date('D', strtotime($start_date_full_format));
                    $start_month = date('m/d', strtotime($start_date_full_format));
                    $start_time = date('D, M d Y', strtotime($start_date_full_format));
                    $event_start_time = date('D, M d Y h:i A', strtotime($start_date_full_format));
                    $result[] = array(
                        'booking_id' => $get_bill->id,
                        'booking_title' => $get_bill->booking_title,
                        'booking_location' => $get_bill->booking_location,
                        'booking_date' => $booking_date_format,
                        'booking_time' => '1',
                        'booking_payment_paid' => $user_detail->user_currency . ' ' . $user_detail->user_price,
                        'user_name' => $user_detail->name . ' ' . $user_detail->last_name,
                        'user_image' => $image_data,
                        'stylist_currency' => $get_bill->stylist_currency,
                        'stylist_price' => $get_bill->stylist_price,
                        'meeting_started_time' => $get_bill->meeting_started_time,
                        'booking_started_date' => $start_date,
                        'booking_started_month' => $start_month,
                        'booking_started_time' => $start_time,
                        'booking_started_date_time' => $event_start_time,
                        'meeting_ended_time' => $meeting_ended_time,
                        'meeting_status' => $get_bill->meeting_status,
                        'bill_uploaded_status' => $bill_uploaded_status,
                        'rating_check_status' => $rating_check_status,
                        'rescheduled_time' => $rescheduled_time,
                        'rescheduled_started_meeting' => $rescheduled_started_meeting,
                        'meeting_re_ended_time' => $meeting_re_ended_time,
                        'rescheduled_flag' => $rescheduled_flag,
                        'stylist_id' => $get_bill->stylist_id,
                    );
                }
                return json_encode(array('status' => "200", 'message' => 'all meetings', 'result' => $result));
            }
        }
    }

    //Get calendar booking END
    //all near stylist START
    public function all_near_stylist(Request $request) {
        $latitude = $request->input('lat');
        $longitude = $request->input('lng');
        $user_id = $request->input('user_id');
        $near_stylists = ShoppingLocation::select(DB::raw('*, ( 6367 * acos( cos( radians(' . $latitude . ') ) * cos( radians( lat ) ) * cos( radians( lng ) - radians(' . $longitude . ') ) + sin( radians(' . $latitude . ') ) * sin( radians( lat ) ) ) ) AS distance'))
                ->having('distance', '<', 25)
                ->leftjoin('users', 'users.id', '=', 'shopping_locations.user_id')
                ->where('shopping_locations.user_id', '!=', $user_id)
                ->where('users.type', 'stylist')
                ->where('users.avail_status', 'online')
                ->orderBy('distance')
                ->get();
        if (count($near_stylists) == '0') {
            $all_stylist_data = [];
        } else {
            //print_r($near_stylists); die;
            foreach ($near_stylists as $near_stylist) {
                $all_users = User::orderBy('share_count', 'desc')->where('type', 'stylist')->where('avail_status', 'online')->where('id', $near_stylist->user_id)->get();
//                print_r($all_users); die;
                if (isset($all_users) && !empty($all_users)) {
                    foreach ($all_users as $all_user) {
                        if (isset($all_user->image) && !empty($all_user->image)) {
                            $image_data = url('../storage/files/') . '/' . $all_user->image;
                        } else {
                            $image_data = '';
                        }

                        $shoping_loc_count = ShoppingLocation::where('lat', $near_stylist->lat)->where('user_id', '!=', $user_id)->where('lng', $near_stylist->lng)->count();
                        $shoping_loc_get = ShoppingLocation::where('lat', $near_stylist->lat)->where('user_id', '!=', $user_id)->where('lng', $near_stylist->lng)->get();
                        if ($shoping_loc_count == '0') {
                            $loc_data1 = array();
                        } else {
                            $loc_data1 = [];
//                        foreach ($shoping_loc_get as $shoping_loc) {
                            $loc_data1[] = array(
                                'id' => $shoping_loc_get[0]->id,
                                'lat' => $shoping_loc_get[0]->lat,
                                'lng' => $shoping_loc_get[0]->lng,
                                'city' => $shoping_loc_get[0]->city,
                            );
//                        }
                        }

                        //Get rating of specialist START
                        $get_rating_data = DB::table('ratings')->select('rate')->where('stylist_id', $all_user->id)->get();
                        $get_data_rate_total = 0;
                        foreach ($get_rating_data as $get_rating_datas) {
                            $get_data_rate_total += $get_rating_datas->rate;
                        }

                        if (count($get_rating_data) != '0') {
                            $average = $get_data_rate_total / count($get_rating_data);
                            $count_rating = count($get_rating_data);
                        } else {
                            $average = '0';
                            $count_rating = '0';
                        }
                        //Get rating of specialist END

                        $loc_data12 = array_unique($loc_data1, SORT_REGULAR);
                        //picked up END
                        $all_stylist_data[] = array(
                            'id' => $all_user->id,
                            'first_name' => $all_user->name,
                            'last_name' => $all_user->last_name,
                            'email' => $all_user->email,
                            'image' => $image_data,
                            'user_location' => $loc_data1,
                            'rating_percentage' => ceil($average),
                            'rating_count' => $count_rating,
                        );
                    }
                } else {
                    $all_stylist_data = [];
                }
            }
        }

        return json_encode(array('status' => "200", 'message' => 'all stylist data', 'near_stylist' => $all_stylist_data));
    }

    //all near stylist END
    //all near stylist START
    public function all_picked_stylist(Request $request) {
        $latitude = $request->input('lat');
        $longitude = $request->input('lng');
        $user_id = $request->input('user_id');
        $picked_up_user_data = UserSpecialty::where('user_id', $user_id)->get();
//        print_r($picked_up_user_data);
//        die;
        if (count($picked_up_user_data) != '0') {
            foreach ($picked_up_user_data as $picked_up_user_datas) {
                $pick = DB::table('user_specialties')
                        ->leftjoin('users', 'users.id', '=', 'user_specialties.user_id')
                        ->select('user_specialties.*')
                        ->where('user_specialties.user_id', '!=', $user_id)
                        ->where('user_specialties.cat_id', $picked_up_user_datas->cat_id)
                        ->where('users.type', 'stylist')
                        ->where('users.avail_status', 'online')
                        ->first();
//                $pick = DB::table('user_specialties')->where('cat_id', $picked_up_user_datas->cat_id)->where('user_id', '!=', $user_id)->first();
                $pick_count = DB::table('user_specialties')->where('cat_id', $picked_up_user_datas->cat_id)->where('user_id', '!=', $user_id)->count();


                if ($pick_count == 0) {
                    continue;
                }
//                print_r($pick);
//                die;
                if (isset($pick)) {
                    if (property_exists($pick, 'user_id') || !empty($pick)) {

                        $all_users = DB::table('users')->where('id', $pick->user_id)->where('avail_status', 'online')->where('type', 'stylist')->get();
                        foreach ($all_users as $all_user) {
                            if (isset($all_user->image) && !empty($all_user->image)) {
                                $image_data = url('../storage/files/') . '/' . $all_user->image;
                            } else {
                                $image_data = '';
                            }

                            $shoping_loc_count = ShoppingLocation::where('user_id', $all_user->id)->count();
                            $shoping_loc_get = ShoppingLocation::where('user_id', $all_user->id)->get();
                            if ($shoping_loc_count == '0') {
                                $loc_data2 = array();
                            } else {
//                            foreach ($shoping_loc_get as $shoping_loc) {
                                $loc_data2[] = array(
                                    'id' => $shoping_loc_get[0]->id,
                                    'lat' => $shoping_loc_get[0]->lat,
                                    'lng' => $shoping_loc_get[0]->lng,
                                    'city' => $shoping_loc_get[0]->city,
                                );
//                            }
                            }
                            //Get rating of specialist START
                            $get_rating_data = DB::table('ratings')->select('rate')->where('stylist_id', $all_user->id)->get();
                            $get_data_rate_total = 0;
                            foreach ($get_rating_data as $get_rating_datas) {
                                $get_data_rate_total += $get_rating_datas->rate;
                            }

                            if (count($get_rating_data) != '0') {
                                $average = $get_data_rate_total / count($get_rating_data);
                                $count_rating = count($get_rating_data);
                            } else {
                                $average = '0';
                                $count_rating = '0';
                            }
                            //Get rating of specialist END
                            $all_pick_data[] = array(
                                'id' => $all_user->id,
                                'first_name' => $all_user->name,
                                'last_name' => $all_user->last_name,
                                'email' => $all_user->email,
                                'image' => $image_data,
                                'image' => $image_data,
                                'image' => $image_data,
                                'user_location' => array_unique($loc_data2, SORT_REGULAR),
                                'rating_percentage' => ceil($average),
                                'rating_count' => $count_rating,
                            );
                        }
                    } else {
                        $all_pick_data = [];
                    }
                } else {
                    $all_pick_data = [];
                    $all_stylist_data = [];
                }


//                    print_r($pick); 
            }
            //die;
            $all_pick_datas1 = array_unique($all_pick_data, SORT_REGULAR);
            $all_pick_datas = array_values($all_pick_datas1);
        } else {
            $all_pick_datas = [];
        }

        return json_encode(array('status' => "200", 'message' => 'all stylist data', 'picked_for_you' => $all_pick_datas));
    }

    //all near stylist END
    //all popular stylist START
    public function all_popular_stylist(Request $request) {
        $all_users = User::orderBy('share_count', 'desc')->where('type', 'stylist')->with('shopping_locations', 'user_speciality')->where('id', '!=', '2')->where('avail_status', 'online')->where('id', '!=', $request->input('user_id'))->take(10)->get();
        $all_users_count = User::orderBy('share_count', 'desc')->where('type', 'stylist')->with('shopping_locations', 'user_speciality')->where('id', '!=', '2')->where('avail_status', 'online')->where('id', '!=', $request->input('user_id'))->count();
        if ($all_users_count == '0') {
            $all_users_data = [];
        } else {
//            print_r($all_users_count); die;
            foreach ($all_users as $all_user) {
                if (isset($all_user->image) && !empty($all_user->image)) {
                    $image_data = url('../storage/files/') . '/' . $all_user->image;
                } else {
                    $image_data = '';
                }

                if (count($all_user['shopping_locations']) == '' || count($all_user['shopping_locations']) == '0') {
                    $loc_data = array();
                } else {
//                    foreach ($all_user['shopping_locations'] as $shoping_loc) {
                    $loc_data[] = array(
                        'id' => $all_user['shopping_locations'][0]->id,
                        'lat' => $all_user['shopping_locations'][0]->lat,
                        'lng' => $all_user['shopping_locations'][0]->lng,
                        'city' => $all_user['shopping_locations'][0]->city,
                    );
//                    }
                }
                //Get rating of specialist START
                $get_rating_data = DB::table('ratings')->select('rate')->where('stylist_id', $all_user->id)->get();
                $get_data_rate_total = 0;
                foreach ($get_rating_data as $get_rating_datas) {
                    $get_data_rate_total += $get_rating_datas->rate;
                }

                if (count($get_rating_data) != '0') {
                    $average = $get_data_rate_total / count($get_rating_data);
                    $count_rating = count($get_rating_data);
                } else {
                    $average = '0';
                    $count_rating = '0';
                }
                //Get rating of specialist END

                $all_users_data[] = array(
                    'id' => $all_user->id,
                    'first_name' => $all_user->name,
                    'last_name' => $all_user->last_name,
                    'email' => $all_user->email,
                    'image' => $image_data,
                    'image' => $image_data,
                    'image' => $image_data,
                    'user_location' => $loc_data,
                    'rating_percentage' => ceil($average),
                    'rating_count' => $count_rating,
                );
            }
        }

        return json_encode(array('status' => "200", 'message' => 'all stylist data', 'popular_stylist' => $all_users_data));
    }

    //all popular stylist END
    //user wallet START
    public function user_wallet(Request $request) {
        $user_id = $request->input('user_id');
        $user_type = $request->input('user_type');
        if ($user_type == 'client') {
            $client_payments = DB::table('first_payment')
                    ->leftjoin('bookings', 'bookings.id', '=', 'first_payment.booking_id')
                    ->select('first_payment.*','bookings.booking_title','bookings.booking_location','bookings.booking_date','bookings.booking_time')
                    ->where('bookings.user_id', '=', $user_id)
                    ->where('bookings.booking_status', '=', '1')
                    ->orderBy('bookings.id','desc')
                    ->get();
            
            if(count($client_payments)!='0'){
                
                foreach($client_payments as $client_payment){
                    $client_payment_history[] = array(
                        'booking_title'=>$client_payment->booking_title,
                        'booking_location'=>$client_payment->booking_location,
                        'booking_date'=>$client_payment->booking_date,
                        'booking_time'=>$client_payment->booking_time,
                        'payment_id'=>$client_payment->payment_id,
                        'payment_amount'=>$client_payment->payment_amount,
                        'payment_currency'=>$client_payment->payment_currency,
                        'payment_status'=>'paid',
                    );
                }
            }else{
                $client_payment_history = [];
            }
            
            
            $wallet_amounts=DB::table('wallet')->where('user_id',$user_id)->get();
            if(count($wallet_amounts)!='0'){
                foreach($wallet_amounts as $wallet_amount){
                    
                    $wallet_data[] = array(
                        'id'=>$wallet_amount->id,
                        'amount'=>$wallet_amount->amount,
                        'status'=>$wallet_amount->withdrawal_status,
                        'date'=>$wallet_amount->created_at,
                    );
                }
            }else{
                $wallet_data = [];
            }
            
            
            
            $wallet_amounts1=DB::table('wallet')->where('user_id',$user_id)->where('withdrawal_status','in')->get();
            $wallet_amounts2=DB::table('wallet')->where('user_id',$user_id)->where('withdrawal_status','out')->get();
            if(count($wallet_amounts1)!='0'){
                $wallet_in_sum = 0;
                foreach($wallet_amounts1 as $wallet_amount1){
                    $wallet_in_sum += $wallet_amount1->amount;
                }
                
                $wallet_out_sum = 0;
                foreach($wallet_amounts2 as $wallet_amount2){
                    $wallet_out_sum += $wallet_amount2->amount;
                }
                $wallet_sum = $wallet_in_sum - $wallet_out_sum;
            }else{
                $wallet_sum = "0";
            }
            
            
            return json_encode(array('status' => "200", 'message' => 'withdrawal data', 'payment_history' => $client_payment_history,'wallet_data'=>$wallet_data,'total_wallet_amount'=>$wallet_sum));
            
        }else{
            return json_encode(array('status' => "201", 'message' => 'this user is not client'));
        }
    }

    //user wallet END
    
    //stylist wallet START
    public function stylist_wallet(Request $request){
        $user_id = $request->input('user_id');
        $user_type = $request->input('user_type');
        if ($user_type == 'stylist'){
            $stylist_all_payments = DB::table('first_payment')
                    ->leftjoin('bookings', 'bookings.id', '=', 'first_payment.booking_id')
                    ->select('first_payment.*','bookings.booking_title','bookings.booking_title','bookings.booking_location','bookings.booking_date','bookings.booking_time','bookings.admin_fees')
                    ->where('bookings.stylist_id', '=', $user_id)
                    ->where('bookings.booking_status', '=', '1')
                    ->orderBy('bookings.id','desc')
                    ->get();
            if(count($stylist_all_payments)!='0'){
                foreach($stylist_all_payments as $stylist_all_payment){
                    $amount_to_pay1 = ($stylist_all_payment->admin_fees / 100) * $stylist_all_payment->payment_amount;
                     $amount_to_pay = $stylist_all_payment->payment_amount - $amount_to_pay1;
                    $payment_history[] = array(
                            'booking_title'=>$stylist_all_payment->booking_title,
                            'booking_location'=>$stylist_all_payment->booking_location,
                            'booking_date'=>$stylist_all_payment->booking_date,
                            'booking_time'=>$stylist_all_payment->booking_time,
                            'payment_id'=>$stylist_all_payment->payment_id,
                            'payment_amount'=>$stylist_all_payment->payment_amount,
                            'payment_currency'=>$stylist_all_payment->payment_currency,
                            'payment_status'=>$stylist_all_payment->withdrawal_status,
                            'admin_fees'=>$stylist_all_payment->admin_fees,
                            'pay_amount'=> number_format((float)$amount_to_pay, 2, '.', ''),
                        );
                }
            }else{ 
                $payment_history = [];
            }
            
            
            $payment_withdrawed = DB::table('first_payment')
                    ->leftjoin('bookings', 'bookings.id', '=', 'first_payment.booking_id')
                    ->select('first_payment.*','bookings.booking_title','bookings.booking_location','bookings.booking_date','bookings.booking_time','bookings.admin_fees')
                    ->where('bookings.stylist_id', '=', $user_id)
                    ->where('bookings.booking_status', '=', '1')
                    ->where('first_payment.withdrawal_status','out')
                    ->orderBy('bookings.id','desc')
                    ->get();
            if(count($payment_withdrawed)!='0'){
                foreach($payment_withdrawed as $payment_withdraweds){
                    $amount_to_pay1 = ($payment_withdraweds->admin_fees / 100) * $payment_withdraweds->payment_amount;
                    $amount_to_pay = $payment_withdraweds->payment_amount - $amount_to_pay1;
                    $payment_withdrawal[] = array(
                            'booking_title'=>$payment_withdraweds->booking_title,
                            'booking_location'=>$payment_withdraweds->booking_location,
                            'booking_date'=>$payment_withdraweds->booking_date,
                            'booking_time'=>$payment_withdraweds->booking_time,
                            'payment_id'=>$payment_withdraweds->payment_id,
                            'payment_amount'=>$payment_withdraweds->payment_amount,
                            'payment_currency'=>$payment_withdraweds->payment_currency,
                            'payment_status'=>$payment_withdraweds->withdrawal_status,
                            'admin_fees'=>$payment_withdraweds->admin_fees,
                            'pay_amount'=> number_format((float)$amount_to_pay, 2, '.', ''),
                        );
                }
            }else{ 
                $payment_withdrawal = [];
            }
            
            
            $wallet_amounts=DB::table('first_payment')
                    ->leftjoin('bookings', 'bookings.id', '=', 'first_payment.booking_id')
                    ->select('first_payment.*','bookings.booking_title','bookings.booking_location','bookings.booking_date','bookings.booking_time','bookings.admin_fees')
                    ->where('bookings.stylist_id', '=', $user_id)
                    ->where('bookings.booking_status', '=', '1')
                    ->where('first_payment.withdrawal_status','in')
                    ->orderBy('bookings.id','desc')
                    ->get();
            if(count($wallet_amounts)!='0'){
                $wallet_sum = 0;
                foreach($wallet_amounts as $wallet_amount){
                    $amount_to_pay1 = ($wallet_amount->admin_fees / 100) * $wallet_amount->payment_amount;
                    $amount_to_pay = $wallet_amount->payment_amount - $amount_to_pay1;
                    $wallet_sum += number_format((float)$amount_to_pay, 2, '.', '');;
                    $wallet_data[] = array(
                        'id'=>$wallet_amount->id,
                        'total_amount'=>$wallet_amount->payment_amount,
                        'payment_amount'=>number_format((float)$amount_to_pay, 2, '.', ''),
                        'admin_fees'=>$wallet_amount->admin_fees,
                        'status'=>$wallet_amount->withdrawal_status,
                        'pay_amount'=> number_format((float)$amount_to_pay, 2, '.', ''),
                    );
                }
            }else{
                $wallet_data = [];
                $wallet_sum = "0";
            }
            
//            'payment_withdrawed'=>$payment_withdrawal,
            return json_encode(array('status' => "200", 'message' => 'withdrawal data', 'payment_history' => $payment_history,'total_wallet_amount'=>$wallet_sum));
        }else{
            return json_encode(array('status' => "201", 'message' => 'this user is not stylist'));
        }
    }
    //stylist wallet END
    
    //user wallet balance START
    public function user_wallet_balance(Request $request){
        $user_id = $request->input('user_id');
        $wallet_amounts1=DB::table('wallet')->where('user_id',$user_id)->where('withdrawal_status','in')->get();
        $wallet_amounts2=DB::table('wallet')->where('user_id',$user_id)->where('withdrawal_status','out')->get();
            if(count($wallet_amounts1)!='0'){
                $wallet_in_sum = 0;
                foreach($wallet_amounts1 as $wallet_amount1){
                    $wallet_in_sum += $wallet_amount1->amount;
                }
                
                $wallet_out_sum = 0;
                foreach($wallet_amounts2 as $wallet_amount2){
                    $wallet_out_sum += $wallet_amount2->amount;
                }
                $wallet_sum = $wallet_in_sum - $wallet_out_sum;
            }else{
                $wallet_sum = "0";
            }
            return json_encode(array('status' => "200", 'message' => 'wallet data','total_wallet_amount'=>$wallet_sum));
    }
    //user wallet balance END
}
 
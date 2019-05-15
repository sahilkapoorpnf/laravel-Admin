<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use Validator;
use Hash;
use Illuminate\Support\Facades\Input;
use App\User;
use App\UserType;
use App\Category;
use App\Term;
use DB;
use Auth;
use Image;


class AdminController extends Controller {

    public function __construct() {
        $this->middleware('auth');
    }

    //Admin dashboard View START
    public function admin() {
        $users_count = DB::table('users')->count();
        $stylist_count = DB::table('users')->where('type','stylist')->count();
        $booking_count = DB::table('bookings')->count();
        $total_earing = DB::table('first_payment')->sum('first_payment.payment_amount');
        return view('admin.admin',get_defined_vars()); 
    }

    //Admin dashboard View END
    //Admin profile page START
    public function admin_profile(Request $request, $id) {
        $user_id = decrypt($id);
        $userData = DB::table('users')->where('id', $user_id)->first();
        if ($request->all()) {
            if (Input::hasFile('image')) {
                //If image is selected START
                $validator = Validator::make(Input::all(), [
                            'username' => 'required',
                            'image' => 'mimes:jpg,png,gif,jpeg'
                                ], [
                            'username.required' => ' Sorry! You can not leave username empty.',
                ]);
                if ($validator->fails()) {
                    $user_id = encrypt($user_id);
                    return redirect('admin/admin-profile/' . $user_id)->withErrors($validator)->withInput();
                } else {
                    $file = Input::file('image');
                    $name = time() . '-' . $file->getClientOriginalName();
                    if ($image = $file->move(storage_path() . '/files/', $name)) {
                        DB::table('users')
                                ->where('id', $user_id)
                                ->update(['name' => $request->username, 'image' => $name]);
                        $user_id = encrypt($user_id);
                        return redirect('admin/admin-profile/' . $user_id)->with('success', 'You have updated the record!');
                    } else {
                        die('here');
                    }
                }
            } else {
                //If image is not selected START
                $validator = Validator::make(Input::all(), [
                            'username' => 'required'
                                ], [
                            'username.required' => ' Sorry! You can not leave username empty.',
                ]);
                if ($validator->fails()) {
                    $user_id = encrypt($user_id);
                    return redirect('admin/admin-profile/' . $user_id)->withErrors($validator)->withInput();
                } else {
                    DB::table('users')
                            ->where('id', $user_id)
                            ->update(['name' => $request->username]);
                    $user_id = encrypt($user_id);
                    return redirect('admin/admin-profile/' . $user_id)->with('success', 'You have updated the record!');
                }
            }
        }
        return view('admin.admin_profile', compact('userData'));
    }

    //Admin profile page END
    //Setting profile password START
    public function admin_setting(Request $request, $id) {
        $user_id = decrypt($id);
        $userData = DB::table('users')->where('id', $user_id)->first();
        if ($request->all()) {
            $validator = Validator::make(Input::all(), [
                        'current_password' => 'required',
                        'password' => 'required|same:password',
                        'password_confirmation' => 'required|same:password',
            ]);
            if ($validator->fails()) {
                $user_id = encrypt($user_id);
                return redirect('admin/admin-setting/' . $user_id)->withErrors($validator)->withInput();
            } else {
                $current_password = Auth::User()->password;
                if (Hash::check($request->current_password, $current_password)) {
                    DB::table('users')
                            ->where('id', $user_id)
                            ->update(['password' =>  Hash::make($request->password)]);
                    $user_id = encrypt($user_id);
                    return redirect('admin/admin-setting/' . $user_id)->with('success', 'Congrats! password has been updated successfully');
                }else{
                    $user_id = encrypt($user_id);
                    return redirect('admin/admin-setting/' . $user_id)->with('error', 'Oops! fill your correct current password');
                }
            }
        }

        return view('admin.admin_setting');
    }

    //Setting profile password END
     
    
    /******************* User Mangement **********************/
    //Add new user START
    public function add_user(Request $request) {
       $userTypes = UserType::where('status','1')->where('id','!=','2')->where('id','!=','3')->get();
        if ($request->all()) {
            $validator = Validator::make(Input::all(), [
                        'username' => 'required',
                        'user_type' => 'required',
                        'email' => 'unique:users|required|email'
                            ], [
                        'username.required' => ' Sorry! You can not leave username empty.',
                        'user_type.required' => ' Sorry! You can not leave user type unselected.',
                        'email.required' => 'Sorry! You can not leave email empty.',
                        'email.unique' => 'Sorry! This email is already registered.',
                        'email.email' => 'Email type must be accordingly.'
            ]);
            if ($validator->fails()) {
                return redirect('admin/add-user')->withErrors($validator)->withInput();
            } else {
                $password = mt_rand(100000, 999999);
                $user = new User;
                $user->name = $request->username;
                $user->email = $request->email;
                $user->password = Hash::make($password);
                $user->type = $request->user_type;
                $user->save();
                return redirect('admin/manage-users')->with('success', 'Congrats! new member has been added to your system');
            }
        }

        return view('admin.user_management.add_user',compact('userTypes'));
    }

    //Add new user END
    //Manage users START
    public function manage_users(Request $request) {
        $url = $request->path();
        $url_data=explode('/', $url);
        if($url_data[1]=='manage-users'){
            $allUsers = DB::table('users')->where('type', '!=', 'admin')->get();
        }elseif($url_data[1]=='manage-stylists'){
            $allUsers = DB::table('users')->where('type', '=', 'stylist')->get();
        }
        return view('admin.user_management.manage_users', get_defined_vars());
    }

    //Manage users END
    
    //Manage Stylist bookings START
    public function stylist_bookings(Request $request,$id){
        $all_booking = DB::table('bookings')->where('stylist_id',$id)->orderBy('id','desc')->get();
        return view('admin.bookings_management.manage_bookings', compact('all_booking'));
    }
    //Manage Stylist bookings END
    
    //Update users status START
    public function update_users_status(Request $request, $id) {
        $user_id = decrypt($id);
        $userData = DB::table('users')->where('id', $user_id)->first();
        if ($userData->status == '1') {
            DB::table('users')
                    ->where('id', $user_id)
                    ->update(['status' => '0']);
        }
        if ($userData->status == '0') {
            DB::table('users')
                    ->where('id', $user_id)
                    ->update(['status' => ' 1']);
        }
        return redirect()->back()->with('success', 'Record has been updated successfully!');
    }
    //Update users status END
    //Delete user START
    public function delete_user(Request $request, $id) {
        $user_id = decrypt($id);
        DB::table('users')->where('id', $user_id)->delete();
        return redirect()->back()->with('success', 'Record is no more with your system!');
    }

    //Delete user END
    //Update users status START
    public function edit_user(Request $request, $id) {
        $user_id = decrypt($id);
        $userData = DB::table('users')->where('id', $user_id)->first();
        if ($request->all()) {
            $validator = Validator::make(Input::all(), [
                        'username' => 'required'
                            ], [
                        'username.required' => ' Sorry! You can not leave username empty.',
            ]);
            if ($validator->fails()) {
                return redirect('admin/edit-user/' . $id)->withErrors($validator)->withInput();
            } else {
                DB::table('users')
                        ->where('id', $user_id)
                        ->update(['name' => $request->username]);
                return redirect('admin/manage-users')->with('success', 'You have updated the record!');
            }
        }
        return view('admin.user_management.edit_user', compact('userData'));
    }
    //Update users status END
    /******************* User Mangement **********************/
    
    /******************* User Type Mangement **********************/
    //Add new user type START
    public function add_user_type(Request $request) {
        if ($request->all()) {
            $validator = Validator::make(Input::all(), [
                        'title' => 'required',
                            ], [
                        'title.required' => ' Sorry! You can not leave title empty.',
            ]);
            if ($validator->fails()) {
                return redirect('admin/add-user-type')->withErrors($validator)->withInput();
            } else {
                $password = mt_rand(100000, 999999);
                $user_type = new UserType;
                $user_type->title = $request->title;
                $user_type->save();
                return redirect('admin/manage-users-type')->with('success', 'Congrats! new record has been added to your system');
            }
        }
        return view('admin.user_type_management.add_user_type');
    }
    //Add new user type END
    
    //Manage users type START
    public function manage_users_type(Request $request) {
        $allUserTypes = DB::table('user_types')->where('id','!=','2')->where('id','!=','3')->get();
        return view('admin.user_type_management.manage_users_type', compact('allUserTypes'));
    }
    //Manage users type END
    
    //Update users status START
    public function update_users_type_status(Request $request, $id) {
        $user_id = decrypt($id);
        $userData = DB::table('user_types')->where('id', $user_id)->first();
        if ($userData->status == '1') {
            DB::table('user_types')
                    ->where('id', $user_id)
                    ->update(['status' => '0']);
        }
        if ($userData->status == '0') {
            DB::table('user_types')
                    ->where('id', $user_id)
                    ->update(['status' => ' 1']);
        }
        return redirect()->back()->with('success', 'Record has been updated successfully!');
    }
    //Update users status END
    
    //Delete user START
    public function delete_user_type(Request $request, $id) {
        $user_id = decrypt($id);
        DB::table('user_types')->where('id', $user_id)->delete();
        return redirect()->back()->with('success', 'Record is no more with your system!');
    }
    //Delete user END
    
    //Update users status START
    public function edit_user_type(Request $request, $id) {
        $user_id = decrypt($id);
        $userData = DB::table('user_types')->where('id', $user_id)->first();
        if ($request->all()) {
            $validator = Validator::make(Input::all(), [
                        'title' => 'required'
                            ], [
                        'title.required' => ' Sorry! You can not leave title empty.',
            ]);
            if ($validator->fails()) {
                return redirect('admin/edit-user-type/' . $id)->withErrors($validator)->withInput();
            } else {
                DB::table('user_types')
                        ->where('id', $user_id)
                        ->update(['title' => $request->title]);
                return redirect('admin/manage-users-type')->with('success', 'You have updated the record!');
            }
        }
        return view('admin.user_type_management.edit_user_type', compact('userData'));
    }
    //Update users status END
    /******************* User Type Mangement **********************/
    
    
    
    
    /******************* Category Mangement **********************/
    //Add new user type START
    public function add_category(Request $request) {
        if ($request->all()) {
            $validator = Validator::make(Input::all(), [
                        'title' => 'required',
                            ], [
                        'title.required' => ' Sorry! You can not leave title empty.',
            ]);
            if ($validator->fails()) {
                return redirect('admin/add-category')->withErrors($validator)->withInput();
            } else {
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
                        $file->move(storage_path() . '/category_files/', $fileNameUnique);
                       
                    }
                    $imageData = $fileNameUnique;
                } else {
                    $imageData = '';
                }
                $user_type = new Category;
                $user_type->title = $request->title;
                $user_type->user_id = Auth::user()->id;
                $user_type->image = $imageData;
                $user_type->save();
                return redirect('admin/manage-category')->with('success', 'Congrats! new record has been added to your system');
            }
        }
        return view('admin.categories_management.add_category');
    }
    //Add new user type END
    
    //Manage users type START
    public function manage_category(Request $request) {
        $allUserTypes = DB::table('categories')->get();
        return view('admin.categories_management.manage_categories', compact('allUserTypes'));
    }
    //Manage users type END
    
    //Update users status START
    public function update_category_status(Request $request, $id) {
        $user_id = decrypt($id);
        $userData = DB::table('categories')->where('id', $user_id)->first();
        if ($userData->status == '1') {
            DB::table('categories')
                    ->where('id', $user_id)
                    ->update(['status' => '0','user_id' => Auth::user()->id]);
        }
        if ($userData->status == '0') {
            DB::table('categories')
                    ->where('id', $user_id)
                    ->update(['status' => ' 1','user_id' => Auth::user()->id]);
        }
        return redirect()->back()->with('success', 'Record has been updated successfully!');
    }
    //Update users status END
    
    //Delete user START
    public function delete_category(Request $request, $id) {
        $user_id = decrypt($id);
        DB::table('categories')->where('id', $user_id)->delete();
        return redirect()->back()->with('success', 'Record is no more with your system!');
    }
    //Delete user END
    
    //Update users status START
    public function edit_category(Request $request, $id) {
        $user_id = decrypt($id);
        $userData = DB::table('categories')->where('id', $user_id)->first();
        if ($request->all()) {
            $validator = Validator::make(Input::all(), [
                        'title' => 'required'
                            ], [
                        'title.required' => ' Sorry! You can not leave title empty.',
            ]);
            if ($validator->fails()) {
                return redirect('admin/edit-category/' . $id)->withErrors($validator)->withInput();
            } else {
                if ($file = $request->hasFile('image')) {
                    $file = $request->file('image');
                    $imageType = $file->getClientmimeType();
                    
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
                        $file->move(storage_path() . '/category_files/', $fileNameUnique);
                    }
                    $imageData = $fileNameUnique;
                } else {
                    $imageData = '';
                }
                
                DB::table('categories')
                        ->where('id', $user_id)
                        ->update(['title' => $request->title,'image'=>$imageData,'user_id' => Auth::user()->id]);
                return redirect('admin/manage-category')->with('success', 'You have updated the record!');
            }
        }
        return view('admin.categories_management.edit_category', compact('userData'));
    }
    //Update users status END
    /******************* Category Mangement **********************/
    
    
    /******************* Terms Mangement **********************/
    //Add new terms START
    public function add_terms(Request $request) {
        if ($request->all()) {
            $validator = Validator::make(Input::all(), [
                        'title' => 'required',
                        'description' => 'required',
                            ], [
                        'title.required' => ' Sorry! You can not leave title empty.',
                        'description.required' => ' Sorry! You can not leave description empty.',
            ]);
            if ($validator->fails()) {
                return redirect('admin/add-terms')->withErrors($validator)->withInput();
            } else {
                $user_type = new Term;
                $user_type->title = $request->title;
                $user_type->description = $request->description;
                $user_type->user_id = Auth::user()->id;
                $user_type->save();
                return redirect('admin/manage-terms')->with('success', 'Congrats! new record has been added to your system');
            }
        }
        return view('admin.terms_management.add_terms');
    }
    //Add new terms END
    
    //Manage terms START
    public function manage_terms(Request $request) {
        $allUserTypes = DB::table('terms')->orderBy('id','desc')->get();
        return view('admin.terms_management.manage_terms', compact('allUserTypes'));
    }
    //Manage terms END
    
    //Update terms status START
    public function update_terms_status(Request $request, $id) {
        $user_id = decrypt($id);
        $userData = DB::table('terms')->where('id', $user_id)->first();
        if ($userData->status == '1') {
            DB::table('terms')
                    ->where('id', $user_id)
                    ->update(['status' => '0','user_id' => Auth::user()->id]);
        }
        if ($userData->status == '0') {
            DB::table('terms')
                    ->where('id', $user_id)
                    ->update(['status' => ' 1','user_id' => Auth::user()->id]);
        }
        return redirect()->back()->with('success', 'Record has been updated successfully!');
    }
    //Update terms status END
    
    //Delete terms START
    public function delete_terms(Request $request, $id) {
        $user_id = decrypt($id);
        DB::table('terms')->where('id', $user_id)->delete();
        return redirect()->back()->with('success', 'Record is no more with your system!');
    }
    //Delete terms END
    
    //Update terms status START
    public function edit_terms(Request $request, $id) {
        $user_id = decrypt($id);
        $userData = DB::table('terms')->where('id', $user_id)->first();
        if ($request->all()) {
            $validator = Validator::make(Input::all(), [
                        'title' => 'required',
                        'description' => 'required'
                            ], [
                        'title.required' => ' Sorry! You can not leave title empty.',
                        'description.required' => ' Sorry! You can not leave description empty.',
            ]);
            if ($validator->fails()) {
                return redirect('admin/edit-terms/' . $id)->withErrors($validator)->withInput();
            } else {
                DB::table('terms')
                        ->where('id', $user_id)
                        ->update(['title' => $request->title,'description' => $request->description,'user_id' => Auth::user()->id]);
                return redirect('admin/manage-terms')->with('success', 'You have updated the record!');
            }
        }
        return view('admin.terms_management.edit_terms', compact('userData'));
    }
    //Update terms status END
    /******************* Terms Mangement **********************/
    
    /******************* Booking Mangement **********************/
    public function manage_bookings(Request $request){
        $all_booking = DB::table('bookings')->orderBy('id','desc')->get();
        return view('admin.bookings_management.manage_bookings', compact('all_booking'));
    }
    
    //view booking START
    public function view_booking(Request $request,$id){
        $user_id = decrypt($id);
        $get_detail=DB::table('bookings')->where('id', $user_id)->first();
        $booking_data1 = DB::table('bookings')
                    ->leftjoin('first_payment', 'first_payment.booking_id', '=', 'bookings.id')
                    ->leftjoin('meeting_rescheduled', 'meeting_rescheduled.booking_id', '=', 'bookings.id')
                    ->select('first_payment.*','bookings.*','meeting_rescheduled.time','meeting_rescheduled.meeting_scheduled_time')
                    ->where('bookings.id', '=', $user_id)
                    ->get();
        foreach($booking_data1 as $booking_datas11){
            $booking_data[] = (array)$booking_datas11;
        }
        foreach($booking_data as $key=>$booking_datas){
            $user_data=DB::table('users')->where('id',$booking_datas['user_id'])->first();
            $stylist_data=DB::table('users')->where('id',$booking_datas['stylist_id'])->first();
            $booking_data[$key]['get_user_name'] = $user_data->name.' '.$user_data->last_name;
            $booking_data[$key]['get_stylist_name'] = $stylist_data->name.' '.$stylist_data->last_name;
            $booking_data[$key]['stylist_email'] = $stylist_data->email;
            $booking_data[$key]['stylist_phone'] = $stylist_data->phone;
        }
        foreach ($booking_data as $booking_dataC){
            $final_data[] = (object)$booking_dataC;
        }
//        echo "<pre>"; print_r($final_data); die;
        return view('admin.bookings_management.view_booking', get_defined_vars());
    }
    //view booking END
    
    //Delete terms START
    public function delete_booking(Request $request, $id) {
        $user_id = decrypt($id);
        DB::table('bookings')->where('id', $user_id)->delete();
        return redirect()->back()->with('success', 'Record is no more with your system!');
    }
    //Delete terms END
    /******************* Booking Mangement **********************/
    
    
    
    
    /******************* Mobile App Static **********************/
    //Update flash screen START
    public function update_flash_screen(Request $request){
        $userData=DB::table('flashes')->where('id','1')->first();
        if ($request->all()) {
            if (Input::hasFile('images')) {
                //If image is selected START
                $validator = Validator::make(Input::all(), [
                            'title' => 'required',
                            'short_description' => 'required',
                                ], [
                            'title.required' => ' Sorry! You can not leave title empty.',
                            'short_description.required' => ' Sorry! You can not leave description empty.',
                ]);
                if ($validator->fails()) {
                    return redirect('admin/update-flash-screen')->withErrors($validator)->withInput();
                } else {
                    $file = Input::file('images');
                    //echo "<pre>"; print_r($file); die;
                    foreach($file as $files){
                        $name = time() . '-' . $files->getClientOriginalName();
                        $image = $files->move(storage_path() . '/flash_images/', $name);
                         $filesAll[] = $name;
                    }
                    $images_name= implode(",",$filesAll);
                        DB::table('flashes')
                                ->where('id', '1')
                                ->update(['title' => $request->title,'short_description' => $request->short_description, 'images' => $images_name]);
                        return redirect('admin/update-flash-screen')->with('success', 'You have updated the record!');
                    
                }
            } else {
                //If image is not selected START
                $validator = Validator::make(Input::all(), [
                            'title' => 'required',
                            'short_description' => 'required'
                                ], [
                            'title.required' => ' Sorry! You can not leave title empty.',
                            'short_description.required' => ' Sorry! You can not leave description empty.',
                ]);
                if ($validator->fails()) {
                    return redirect('admin/update-flash-screen')->withErrors($validator)->withInput();
                } else {
                    DB::table('flashes')
                            ->where('id', '1')
                            ->update(['title' => $request->title,'short_description' => $request->short_description]);
                    return redirect('admin/update-flash-screen')->with('success', 'You have updated the record!');
                }
            }
        }
        return view('admin.mobile.flash_screen', compact('userData'));
    }
    //Update flash screen END
    
    //update refer amount START
    public function update_refer_amount(Request $request){
        $referData=DB::table('set_refer_amount')->where('id','1')->first();
        if ($request->all()) {
            $validator = Validator::make(Input::all(), [
                            'amount' => 'required|numeric',
                                ], [
                            'amount.required' => ' Sorry! You can not leave amount empty.',
                ]);
                if ($validator->fails()) {
                    return redirect('admin/update-refer-amount')->withErrors($validator)->withInput();
                } else {
                        DB::table('set_refer_amount')
                                ->where('id', '1')
                                ->update(['amount' => $request->amount]);
                        return redirect('admin/update-refer-amount')->with('success', 'Amount has been updated successfully!');
                    
                }
        }
        return view('admin.mobile.refer_amount', compact('referData'));
    }
    //update refer amount END
    
    //update booking amount START
    public function admin_booking_amount(Request $request){
        $referData=DB::table('admin_fees')->where('id','1')->first();
        if ($request->all()) {
            $validator = Validator::make(Input::all(), [
                            'amount' => 'required|numeric',
                                ], [
                            'amount.required' => ' Sorry! You can not leave amount empty.',
                ]);
                if ($validator->fails()) {
                    return redirect('admin/admin-booking-amount')->withErrors($validator)->withInput();
                } else {
                        DB::table('admin_fees')
                                ->where('id', '1')
                                ->update(['amount' => $request->amount]);
                        return redirect('admin/admin-booking-amount')->with('success', 'Amount has been updated successfully!');
                    
                }
        }
        return view('admin.mobile.profit_amount', compact('referData'));
    }
    //update booking amount END
    /******************* Mobile App Static **********************/
}

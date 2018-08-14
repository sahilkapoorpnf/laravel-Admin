<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use Validator;
use Hash;
use Illuminate\Support\Facades\Input;
use App\User;
use DB;
use Auth;
use Image;

class AdminController extends Controller {

    public function __construct() {
        $this->middleware('auth');
    }

    //Admin dashboard View START
    public function admin() {
        return view('admin.admin');
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
    //Add new user START
    public function add_user(Request $request) {
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

        return view('admin.user_management.add_user');
    }

    //Add new user END
    //Manage users START
    public function manage_users(Request $request) {
        $allUsers = DB::table('users')->where('type', '!=', 'admin')->get();
        return view('admin.user_management.manage_users', compact('allUsers'));
    }

    //Manage users END
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
                return redirect('admin/edit-user/' . $user_id)->withErrors($validator)->withInput();
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
}

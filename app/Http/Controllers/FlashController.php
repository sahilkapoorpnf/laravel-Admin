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
class FlashController extends Controller {

    public function __construct() {
       
    }
    //Get Flash Data START
    public function flash_data(Request $request){
        $data=Flash::where('status','1')->first();
        $result['id'] = $data->id;
        $result['title'] = $data->title;
        $result['short_description'] = $data->short_description;
        $images=explode(',',$data->images);
        foreach($images as $image){
            $result1['images'][]=url('../storage/flash_images/'.$image);
        }
        $result['images']=$result1['images']; 
        //$result['images'] = storage_path().'/flash_images/'.$data->images;
        return json_encode(array('status' => "200", 'message' => 'Flash screen data', 'data' => $result));
    }
    //Get Flash Data END
}

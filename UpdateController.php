<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Latestupdate;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use DB;
class UpdateController extends Controller
{
    public function getAllLatestUpdated(Request $request){
        $data   =  Latestupdate::with('user')->get();
        return view('latest-updates',['data'=>$data]);
    }

    public function AddLatestUpdated(Request $request){
        $data = '';
        return view('add-latest-updates',['data'=>$data]);
    }
    
    public function editLatestUpdated(Request $request,$id){
        $data = Latestupdate::where(['id'=>$id])->first();
        return view('add-latest-updates',['data'=>$data]);

    }

    public function addUpdateLatestNews(Request $request){
        $user  =  Auth::user()->id;
        if($request->id){
            Latestupdate::where(['id'=>$request->id])->update([
                    'user_id'       =>  $user,
                    'title'         =>  $request->title,
                    'url'           =>  $request->url,
                    'description'   =>  $request->description,
                    'status'        =>  $request->status,
                    'updated_at'    =>  date('Y-m-d h:m:s'),
            ]);
            return redirect('latest-updates')->with('success','Record updated successfully!');
        }else{
            $validator = Validator::make($request->all(),[
                'title'  => ['bail', 'required', 'string', 'max:255'],
                'url'  => [ 'required'],
            ]);
            if($validator->fails()){
                $errors = $validator->errors();
                foreach($errors->all() as $key => $value){
                    return redirect()->back()->with('error',ucfirst($value));
                }
            }else{
                Latestupdate::insert([
                        'user_id'       =>  $user,
                        'title'         =>  $request->title,
                        'url'           =>  $request->url,
                        'description'   =>  $request->description,
                        'status'        =>  ($request->status)?$request->status:1,
                        'created_at'    =>   date('Y-m-d h:m:s'),
                ]);
                return redirect('latest-updates')->with('success','Record added successfully!');
            }
        }

    }

    public function changeStatusUpdates(Request $request){
        $getdata  = Latestupdate::where(['id'=>$request->id])->first();
        if($getdata->status===1){
            $results = Latestupdate::where(['id'=>$request->id])->update([
                'status'=>0,
            ]);
        }else{
            $results = Latestupdate::where(['id'=>$request->id])->update([
                'status'=>1,
            ]);
        }
        return \Response::json(['status'=>'success','message'=>'Change status successfully!','data'=>$results],200);
    }

    public function deleteLatestUpdates(Request $request){
        $data  = Latestupdate::where(['id'=>$request->id])->delete();
        return \Response::json(['status'=>'success','message'=>'Delete records successfully!','data'=>$data],200);
    }

}
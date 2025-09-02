<?php

namespace App\Http\Controllers;

use App\Models\API\Role;
use App\Models\InventoryMap;
use App\Models\DeoUser;
use App\Models\Zonestock; 
use App\Models\Districts;
use App\Models\Divisions;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class OperatorIdController extends Controller
{

    public function index(){
        $roles = Role::whereIn('name', ['zone','district', 'deo'])->pluck('id');
        $zoneUsers = User::whereIn('role_id', $roles)->with(['getDeoUser.zone'])->get();
        return view('operatorId.index', compact('zoneUsers'));
    }
    
    public function editUser($id){
        $userData = User::where('id', $id)->first();
        $zoneData = [];
        $zone_id = '';
        $getDistrict = [];
        if($userData['role'] == 'zone'){
            $user_id = $userData['id'];
            $zone_id = $userData['zone_id'];
            $zoneData = Zone::all();
        }else if($userData['role'] == 'district'){
            $user_id = $userData['id'];
            $zoneData = Zone::all();
            $userGetData = DeoUser::where('user_id', $user_id)->first();
            $zone_id = $userGetData['zone_id'];
            $divisions = Divisions::where('zone_id', $zone_id)->get();
            $getDistrict = [];
            foreach($divisions as $division){
                $districts = Districts::where('division_id', $division['id'])->get();
                foreach($districts as $district){
                    $getDistrict[] = $district;
                }
            }
            
        }else if($userData['role'] == 'deo'){
            $user_id = $userData['id'];
            $zoneData = Zone::all();
            $userGetData = DeoUser::where('user_id', $user_id)->first();
            $zone_id = $userGetData['zone_id'];
            $divisions = Divisions::where('zone_id', $zone_id)->get();
            $getDistrict = [];
            foreach($divisions as $division){
                $districts = Districts::where('division_id', $division['id'])->get();
                foreach($districts as $district){
                    $getDistrict[] = $district;
                }
            }
            
        }
        return view('operatorId.userEdit', compact('userData','zoneData', 'zone_id', 'getDistrict', 'user_id'));
    }

    public function updateUser(Request $request){
        $user_id = $request->user_id;
        $FirstName = $request->FirstName;
        $name = $request->name;
        $email = $request->email;
        $password = $request->password;
        $select_zone = $request->select_zone;

        $user = User::findOrFail($user_id);
        $dataUpdate = [
            'FirstName' => $request->FirstName,
            'name' => $request->name,
            'email' => $request->email,
            'zone_id' => $select_zone,
            'password' => Hash::make($request['password']),
        ];
        if(!empty($request->select_district)){
            $dataUpdate['district_id'] = $request->select_district;
        }
        $user->update($dataUpdate);
        
        $update_data_deo =  DeoUser::where('user_id', $user_id)->first();
        $updateDeo = [ 'zone_id' => $select_zone ];
        if(!empty($request->select_district)){
            $updateDeo['district_id'] = $request->select_district;
        }
        $update_data_deo->update($updateDeo);
        return redirect()->route('edit-user', $user_id)->with('success', 'User updated successfully.');
    }

    public function userDelete($id) {
        $event = User::findOrFail($id);
        $event->delete();
        InventoryMap::where('assign_user_id', $id)->delete();
        InventoryMap::where('user_id', $id)->delete();
        DeoUser::where('user_id', $id)->delete();
        return redirect()->back()->with('success', 'User deleted successfully!');
    }
}
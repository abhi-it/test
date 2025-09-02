<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use File;
use App\Models\Divisions;
use App\Models\DeoUser;
use App\Models\Districts;
use App\Models\API\Role;
use App\Models\Zone;
use App\Models\API\Servicerequest;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use DB;

class ZoneDashBoardController extends Controller{

    public function zoneInventory(){    
        $user_id = Auth::user()->id;
        $zoneUsers = User::where('id', $user_id)->first();
        $zone_id = $zoneUsers['zone_id'];
        $districtUsers =  DeoUser::where('zone_id', $zone_id)
                    ->where('district_id', '>', 0)
                    ->whereNull('block_id')
                    ->whereNull('aicenters_id')
                    ->get();

        $districtUserData = [];
        foreach($districtUsers as $districtUser){
            $districtUserId = $districtUser['user_id'];
            $district_id = $districtUser['district_id'];
            
            $districtUser = User::where('id', $districtUserId)->first();
            $districtName = Districts::where('id', $district_id)->first();

            $districtUserData[] = [
                'district_hindi' => $districtName->name_hindi ?? 'N/A',
                'district_eng' => $districtName->name_eng ?? 'N/A',
                'name' => $districtUser->FirstName ?? 'N/A',
                'email' => $districtUser->email ?? 'N/A',
            ];
        }
        return view('zones.zoneInventory', compact('districtUserData'));
    }

    public function createDivisionUser(){
        $user_id = Auth::user()->id;
        $zone_id = DeoUser::where('user_id', $user_id)->first();
        $zones =    Zone::where('id', $zone_id['zone_id'])->get();
        // $division = DeoUser::where('zone_id', $zone_id['zone_id'])->where('division_id', '>', 0)->first();
        // $division_id = $division['division_id'];
        session()->forget('form_step1');
        return view('zones.createDivisionUser', compact('zones'));
    }

    public function divisionStoreData(Request $request){
        $validator = \Validator::make($request->all(), [
            'zone' => 'required|integer',
            // 'division' => 'required|integer',
            'district' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $validatedData = $validator->validated();
        session(['form_step1' => $validatedData]);
        return response()->json(['status' => 200]);
    }

    public function divisionUserStep2(){
        if (!session()->has('form_step1') || empty(session('form_step1'))) {
            return redirect()->route('create-division-user');
        }
        return view('zones.divisionUserStep2');
    }

    public function storeDivisionData(Request $request){
        $validator = \Validator::make($request->all(), [
            'username' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8',
            'confirm_password' => 'required|same:password',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validatedData = $validator->validated();

        $form_step1 = session('form_step1');

        $role = Role::where('name', 'district')->first();
        $role_id = $role ? $role->id : null;

        $district_id = $form_step1['district'];
        $division_id = Districts::where('id', $district_id)->first();


        $user = User::create([
            'name'        => $validatedData['username'],
            'FirstName'   => $validatedData['username'],
            'LastName'    => $validatedData['username'],
            'email'       => $validatedData['email'],
            'password'    => Hash::make($validatedData['password']),
            'district_id' => $division_id['division_id'],
            'division_id' => $division_id['division_id'],
            'role_id'     => $role_id,
            'role'        => 'district',
            'user_type'   => 'District',
        ]);

        $deoUser = DeoUser::create([
            'user_id'       => $user->id,
            'zone_id'       => $form_step1['zone'],
            'division_id'   => $division_id['division_id'],
            'district_id'   => $form_step1['district'],
        ]);

        // foreach($form_step1['aicenters'] as $aiCenterId){
        //     $deoUser = DeoUser::create([
        //         'user_id'       => $user->id,
        //         'zone_id'       => $form_step1['zone'],
        //         'division_id'   => $form_step1['division'],
        //         'district_id' => $form_step1['district'],
        //         'block_id' => $form_step1['block'],
        //         'aicenters_id' => $aiCenterId,
        //     ]);
        // }

        session()->forget('form_step1');
        return response()->json(['status' => 200]);
    }
}
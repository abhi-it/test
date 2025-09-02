<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\CVOOfficers;
use App\Models\Zone;
use App\Models\Divisions;
use App\Models\Districts; 
use App\Models\ZoneDistrict;
use Excel;
use App\Models\Zonestock;
use Illuminate\Support\Facades\Validator;

class ZoneDistrictController extends Controller{

    public function index(){
        $zones = Zone::all();
        return view('zoneDistrict.index',  compact('zones'));
    }

    public function getDistricts($zone_id){
        $district = Districts::all();
        return response()->json($district);
    }

    public function store(Request $request) {
        $request->validate([
            'zone_id' => 'required|exists:zones,id',
            'district_ids' => 'required|array',
            'district_ids.*' => 'exists:districts,id',
        ]);
    
        foreach ($request->district_ids as $district_id) {
            ZoneDistrict::create([
                'zone_id' => $request->zone_id,
                'district_id' => $district_id,
            ]);
        }

        return back()->with('success', 'Selection saved successfully!');
    }

}
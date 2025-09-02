<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Districts;
use App\Models\Avedan;

class SuccessController extends Controller
{
    public function index()
    {
		$districtID = \Session::get('districtID');
		$application_id = \Session::get('application_id');
        $district = Districts::where('id', '=', $districtID)->first();
        $result = Avedan::find($application_id);
        //echo '<pre>';print_r($result);exit;
        return view('success', compact('district', 'result'));
    }
    
    public function succesMedicalReimbursement()
    {
		$districtID = \Session::get('districtID');
        $result = Districts::where('id', '=', $districtID)->first();
        //echo '<pre>';print_r($result);exit;
        return view('success-medical', compact('result'));
    }
}

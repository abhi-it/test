<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\CVOOfficers;
use Excel;
use App\Models\Zonestock;
use Illuminate\Support\Facades\Validator;

class ZoneStockController extends Controller{

    public function index(){
        $count  =  Zonestock::count();
        return view('zone-stock-form',compact('count'));
    }

    public function zoneStoreData(Request $request){
        $validator = Validator::make($request->all(),[
            'demand_section'  => [ 'required'],
            'semen' => [ 'required'],
            'semen_type' => [ 'required'],
        ]);
        if($validator->fails()){
            $errors = $validator->errors();
            foreach($errors->all() as $key => $value){
                 return redirect()->back()->with('error',ucfirst($value));
            }
        }else{
            $bullIds = implode(',',$request->bull_ids);
            $breedType = null;
            if( $request->semen == 'catle'){
                switch ($request->breed) {
                    case 'swadeshi':
                        $request->validate([
                            'breedType1' => 'required|string',
                        ]);
                        $breedType = $request->breedType1;
                        break;

                    case 'hybrids-crossbred':
                        $request->validate([
                            'breedType2' => 'required|string',
                        ]);
                        $breedType = $request->breedType2;
                        break;

                    case 'videshi':
                        $request->validate([
                            'breedType3' => 'required|string',
                        ]);
                        $breedType = $request->breedType3;
                        break;

                    default:
                        return back()->withErrors(['breed' => 'Invalid breed selection.']);
                }
            }else if($request->semen == 'buffalo'){
                $breedType = $request->breedType4;
            }else if($request->semen == 'goat'){
                $breedType = $request->breedType5;
            }
            $request  = new Zonestock([
                'demand_section' => $request->demand_section,
                'breed'          => $request->breed,
                'breed_type'     => $breedType,
                'semen'       => $request->semen,
                'semen_type'  => $request->semen_type,
                'banner'      => $request->banner,
                'dangler'     => $request->dangler,
                'standee'     => $request->standee,
                'pamphlet'    => $request->pamphlet,
                'ai_kit'      => $request->ai_kit,
                'bull_ids'    => $bullIds,
                'container_capacity' =>$request->container_capacity,
                'container'   => $request->container,
                'scheme'      => $request->scheme,
            ]);
            $request->save();
            return redirect()->back()->with('success','Stock data submitted successfully!');
        }
    }


}
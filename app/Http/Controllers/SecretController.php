<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Secret;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;



class SecretController extends Controller
{
    public function addSecret(Request $request){
    
        //checking parameters
        $validated = $request->validate([
            'secret'            => 'required',
            'expireAfter'       => 'required|integer|min:0',
            'expireAfterViews'  => 'required|integer|min:1'
        ]);

        /*The expiration field must be checked, 
        if 0 there is no expiration date, this is achieved with the "maxValue" function. 
        And if the field is a positive number, we add it to the current date*/
        if($request->expireAfter==0){
            
            $expireAfter = Carbon::maxValue();

        }else{
 
            $expireAfter = Carbon::now()
            ->addMinutes($request->expireAfter)
            ->setTimezone('Europe/Budapest')
            ->format("Y-m-d H:i:s.u");
            
        }
        
        //Insert a new row into the "secrets" table
        $data = Secret::create([
            'hash'           => Str::random(20),
            'secretText'     => $request->secret,
            'expiresAt'      => $expireAfter,
            'remainingViews' => $request->expireAfterViews
        ]);
         
        //Sending resposen, with the appropriate type
        $response = [
            'code'=>200,
            'massage'=>"successful operation",
            'data' => $data
        ];

        $type = $request->getAcceptableContentTypes();

        switch($type[0]){
            case "application/xml":
                  return response()->xml($response, 200);
            default:
                  return response()->json($response, 200);                     
        }
      
    }
    

    public function getSecretByHash($hash){

        $type = request()->header('content-type');

        $data = Secret::select('hash','secretText','expiresAt','remainingViews', 'created_at')
                ->where('hash', '=', $hash)
                ->get();
              
        if(!isset($data[0])){
            
            $response = [
                'code'=> 404,
                'massage'=> "Secret not found"
            ];

            switch($type){
                case "application/xml":
                      return response()->xml($response,404);
                default:
                      return response()->json($response,404);                     
            }                
        }
        
        $expiresAt = $data[0]->expiresAt;
        $remaingViews = $data[0]->remainingViews;
        
        if(SecretController::isExpired($expiresAt, $remaingViews)){
            
            $response['code'] = 403;
            $response['massage']= "The secret expired";
            
            switch($type){
                case "application/xml":
                      return response()->xml($response,403);
                default:
                      return response()->json($response,403);                     
            }

        }else{
            
            //modifies the remaingViews table 
            $remaingViews = $remaingViews-1;
            DB::update('update secrets set remainingViews  = '. $remaingViews.' where hash = ?', [$hash]);
            
            $response = [
                'code'=>200,
                'massage'=>"successful operation",
                'data' => $data
            ];

            switch($type){
                case "application/xml":
                      return response()->xml($response,200);
                default:
                      return response()->json($response,200);                     
            }
            
        }   
          
    }

    //This function checks if the secret has expired 
    private function isExpired($expiresAt, $remaingViews){

        $now = Carbon::now()->setTimezone('Europe/Budapest')
                            ->format("Y-m-d H:i:s.u");
        
        if($remaingViews>0){
            if($expiresAt>$now){
                return false;
            };
        }

        return true;
    }


}

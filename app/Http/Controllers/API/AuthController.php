<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

use App\Models\User;

class AuthController extends Controller
{
    // register 
    public function register(Request $request){
        $request->validate([
            'name'=>'required',
            'email'=>'required|email|unique:users',
            'password'=>'required|confirmed',
        ]);

        $user = User::create([
            'name'=>$request->name,
            'email'=>$request->email,
            'password'=>bcrypt($request->password),
            'phone_number'=>$request->phone_number ?? '',
        'national_id' => $request->national_id,
            'id_image'=>$request->id_image ?? '',
            'location'=>$request->location ?? '',
        ]);
        $token = $user->createToken('auth_token')->plainTextToken;
        return response()->json([
            'data'=>$user,
            'access_token'=>$token,
            'token_type'=>'Bearer',
        ]);
    }
    
    // login 
    public function login(Request $request){

        // login validation
        $request->validate([
            'email'=>'required|email',
            'password'=>'required',
        ]);

        // check if user exists
        // if(!Auth::attempt($request->only('email','password'))){
        //     return response()->json([
        //         'message'=>'Invalid login details',
        //     ],401);
        // }

        // get user
        $user = User::where('email',$request->email)->first();

        // check if user exists and password is correct
        if(!$user && !Hash::check($request->password, $user->password)){
            return response()->json([
                'message'=>'Invalid login details',
            ],401);
        }

        // create token
        $token = $user->createToken('auth_token')->plainTextToken;

        // return response
        return response()->json([
            'data'=>$user,
            'access_token'=>$token,
            'token_type'=>'Bearer',
        ]);
    }
}

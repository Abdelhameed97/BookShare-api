<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\StoreUserRequest;
use App\Models\User;

class AuthController extends Controller
{
    // register
    public function register(StoreUserRequest $request){
        // validate request
        $validated = $request->validated();

        // create user
        $user = User::create([
            'name'=>$validated['name'],
            'email'=>$validated['email'],
            'password'=>Hash::make($validated['password']),
            'role'=>$validated['role']??'client', // default role is client
            'phone_number'=>$validated['phone_number']??null,
            'national_id'=>$validated['national_id']??null,
            'location'=>$validated['location']??null,

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

        // get user
        $user = User::where('email',$request->email)->first();

        // check if user exists and password is correct
        if(!$user && !Hash::check($request->password, $user->password)){
            return response()->json([
                'message'=>'Invalid login details',
            ],401);
        }

        // check if user has any tokens
        $tokens = $user->tokens()->count();

        if($tokens > 2){
            return response()->json([
                'message' => 'You have exceeded the maximum number of login attempts.'
            ], 401);
        }else{
            // create token
            $token = $user->createToken('auth_token')->plainTextToken;
        }

        // return response
        return response()->json([
            'data'=>$user,
            'access_token'=>$token,
            'token_type'=>'Bearer',
        ]);
    }

    public function logout(Request $request){
        $request->user()->currentAccessToken()->delete();
        return response()->json([
            'message'=>'Logged out',
        ]);
    }
}
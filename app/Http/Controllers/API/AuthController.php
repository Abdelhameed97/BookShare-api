<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\StoreUserRequest;
use App\Models\User;
use App\Models\Client;
use App\Models\Owner;

class AuthController extends Controller
{
    // register
    public function register(StoreUserRequest $request){
        // validate request
        $validated = $request->validated();
        $validRoles = ['client', 'owner'];
        $role = in_array($validated['role'] ?? null, $validRoles) ? $validated['role'] : 'client';


        // create user
        $user = User::create([
            'name'=>$validated['name'],
            'email'=>$validated['email'],
            'password'=>Hash::make($validated['password']),
            'role'=>$role, // default role is client
            'phone_number'=>$validated['phone_number']??null,
            'national_id'=>$validated['national_id']??null,
            'location'=>$validated['location']??null,

        ]);
         // Create related record based on role
        if ($user->role === 'client') {
            Client::create(['user_id' => $user->id]);
        } elseif ($user->role === 'owner') {
            Owner::create(['user_id' => $user->id]);
        }

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
        if(!$user || !Hash::check($request->password, $user->password)){
            return response()->json([
                'message'=>'Invalid login details',
            ],401);
        }

        // check if user has any tokens

        $maxTokens = 5;

        if ($user->tokens()->count() >= $maxTokens) {
            $user->tokens()->oldest()->first()->delete(); // delete oldest token
        }
        $token = $user->createToken('auth_token')->plainTextToken;


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
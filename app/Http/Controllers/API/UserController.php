<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        // public function isAdmin(): bool
        // {
        //     return $this->role === 'admin';
        // }
        
        if(!auth()->user()->isAdmin()){
            return response()->json(['message' => 'You are not authorized to view users'], 403);
        }        
        return UserResource::collection(User::all());
        // $user = User::all();
        // return response()->json(['users' => $user], 200);

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request)
    {
        //
        // The data is already validated by StoreUserRequest
        if(!auth()->user()->isAdmin()){
            return response()->json(['message' => 'You are not authorized to create users'], 403);
        }
        $validatedData = $request->validated();
        
        // dd($validatedData);
        // Create the user
        $user = User::create([
            'name'     => $validatedData['name'],
            'email'    => $validatedData['email'],
            'password' => Hash::make($validatedData['password']),
            'phone_number' => $validatedData['phone_number'],
            'role'       => $validatedData['role'],
            'national_id'  => $validatedData['national_id'],
            'location'     => $validatedData['location'],
        ]);

        // You can return response as JSON or redirect
        return response()->json([
            'message' => 'User created successfully',
            'user'    => $user,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        
        $user = User::find($id);
        if(!$user){
            return response()->json(['message' => 'User of id ' . $id . ' not found'], 404);
        }
        // admin can view specific user and user can view his own profile
        if(!auth()->user()->isAdmin() && auth()->user()->id != $id){
            return response()->json(['message' => 'You are not authorized to view users'], 403);
        }
        return new UserResource($user);

        // return response()->json(['user' => $user], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, string $id)
    {
        //
        $user = User::find($id);
        if(!$user){
            return response()->json(['message' => 'User of id ' . $id . ' not found'], 404);
        }
        // admin can update any user and user can update his own profile
       if(!auth()->user()->isAdmin() && auth()->user()->id != $id){
            return response()->json(['message' => 'You are not authorized to update users'], 403);
        }
        // The data is already validated by UpdateUserRequest
        $validatedData = $request->validated();
        $user->update($validatedData);
        return $user;

        // return response()->json(['message' => 'User updated'], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = User::find($id);
        if(!$user){
            return response()->json(['message' => 'User of id ' . $id . ' not found'], 404);
        }
        // admin can delete any user
        if(!auth()->user()->isAdmin()){
            return response()->json(['message' => 'You are not authorized to delete users'], 403);
        }
        
        $user->delete();
        return response()->json(['message' => 'User deleted'], 200);

    }
}

<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Client;
use App\Models\Owner;

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
            'location'     => $validatedData['location']?? null,
        ]);

        if ($user->role === 'client') {
            Client::create([
                'user_id' => $user->id,
                // other client-specific fields (optional)
            ]);
        } elseif ($user->role === 'owner') {
            Owner::create([
                'user_id' => $user->id,
                // other owner-specific fields (optional)
            ]);
        }


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
        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'User of id ' . $id . ' not found'], 404);
        }

        // admin can update any user and user can update his own profile
        if (!auth()->user()->isAdmin() && auth()->user()->id != $user->id) {
            return response()->json(['message' => 'You are not authorized to update users'], 403);
        }

        $validatedData = $request->validated();
        $user->update($validatedData);

        // Update related Client or Owner if needed
        if ($user->role === 'client') {
            $client = Client::where('user_id', $user->id)->first();
            if (!$client) {
                Client::create(['user_id' => $user->id]);
            }
            // else: optionally update client info if fields exist
        } elseif ($user->role === 'owner') {
            $owner = Owner::where('user_id', $user->id)->first();
            if (!$owner) {
                Owner::create(['user_id' => $user->id]);
            }
            // else: optionally update owner info if fields exist
        }

        return response()->json([
            'message' => 'User updated successfully',
            'user' => $user
        ]);
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
        if ($user->role === 'client') {
            Client::delete([
                'user_id' => $user->id,
                // other client-specific fields (optional)
            ]);
        } elseif ($user->role === 'owner') {
            Owner::delete([
                'user_id' => $user->id,
                // other owner-specific fields (optional)
            ]);
        }

        $user->delete();
        return response()->json(['message' => 'User deleted'], 200);

    }
}

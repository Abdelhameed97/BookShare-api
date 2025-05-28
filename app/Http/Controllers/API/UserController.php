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
        $validatedData = $request->validated();

        // Create the user
        $user = User::create([
            'name'     => $validatedData['name'],
            'email'    => $validatedData['email'],
            'password' => Hash::make($validatedData['password']),
            'phone_number' => $request->phone_number ?? null,
            'national_id'  => $request->national_id ?? null,
            'id_image'     => $request->id_image ?? null,
            'location'     => $request->location ?? null,
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
        //
        $user= User::findorFail($id);
        return response()->json(['user' => $user], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, string $id)
    {
        //
        $user = User::findorFail($id);

        // The data is already validated by UpdateUserRequest
        $validatedData = $request->validated();
        $user->update($request->all());
        return $user;
        // return response()->json(['message' => 'User updated'], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
        $user = User::findorFail($id);
        $user->delete();
        return response()->json(['message' => 'User deleted'], 200);
    }
}

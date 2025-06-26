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
use Illuminate\Database\Eloquent\ModelNotFoundException;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        if(!$user->isAdmin()){
            return response()->json(['message' => 'You are not authorized to view users'], 403);
        }

        $query = User::query();

        // Filter by role if the request has a 'role' parameter
        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        return UserResource::collection($query->get());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request)
    {
        //
            // dd(auth()->user()); // ⬅️ Debugging line to check the authenticated user

        // The data is already validated by StoreUserRequest
        /** @var \App\Models\User $user */
        $user = $request->user();
        if(!$user->isAdmin()){
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
    public function show(Request $request, string $id)
    {
        
        $user = User::find($id);
        if(!$user){
            return response()->json(['message' => 'User of id ' . $id . ' not found'], 404);
        }
        // admin can view specific user and user can view his own profile
        /** @var \App\Models\User $currentUser */
        $currentUser = $request->user();
        if(!$currentUser->isAdmin() && $currentUser->id != $id){
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
        // 1. Check if user exists
        try {
        $user = User::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => "User of id {$id} not found."], 404);
        }

    
        // 2. Check authorization
        /** @var \App\Models\User $currentUser */
        $currentUser = $request->user();
        if (!$currentUser->isAdmin() && $currentUser->id != $id) {
            return response()->json([
                'message' => 'You are not authorized to update this user.'
            ], 403);
        }
        
        // 3. Check if role is changing
        $oldRole = $user->role;

        // 4. Get validated data and update
        $validatedData = $request->validated();
        
        // Handle password update if provided
        if (isset($validatedData['password'])) {
            // Hash the new password
            $validatedData['password'] = Hash::make($validatedData['password']);
            
            // Remove fields that shouldn't be stored in database
            unset($validatedData['password_confirmation']);
            unset($validatedData['current_password']);
        }
        
        $user->update($validatedData);

        // 5. Sync related models
        $this->syncRelatedRoleModels($user, $oldRole);

        // 6. Return updated user
        return response()->json([
            'message' => 'User updated successfully.',
            'data' => $user
        ]);
    }

    /**
     * Sync Client or Owner model based on user's role.
     */
    private function syncRelatedRoleModels(User $user, string $oldRole): void
    {
        // If role changed from client to owner
        if ($oldRole === 'client' && $user->role !== 'client') {
            Client::where('user_id', $user->id)->delete();
        }

        // If role changed from owner to client
        if ($oldRole === 'owner' && $user->role !== 'owner') {
            Owner::where('user_id', $user->id)->delete();
        }
        
        // If role is client, ensure Client model exists
        if ($user->role === 'client') {
            Client::updateOrCreate(
                ['user_id' => $user->id],
                ['profile_picture' => null, 'preferences' => null] // optional fields
            );
        }

        // If role is owner, ensure Owner model exists
        if ($user->role === 'owner') {
            Owner::updateOrCreate(
                ['user_id' => $user->id],
                ['library_name' => $user->name . "'s Library"] // example field
            );
        }
    }

    /**
     * Remove the specified resource from storage.
     */
   public function destroy(Request $request, string $id)
    {
        // 1. Check if user exists
        $user = User::find($id);
        if (!$user) {
            return response()->json([
                'message' => "User of id {$id} not found"
            ], 404);
        }

        // 2. Authorization check
        /** @var \App\Models\User $currentUser */
        $currentUser = $request->user();
        if (!$currentUser->isAdmin()) {
            return response()->json([
                'message' => 'You are not authorized to delete users'
            ], 403);
        }

        // 3. Delete related records
        if ($user->role === 'client') {
            Client::where('user_id', $user->id)->delete();
        } elseif ($user->role === 'owner') {
            Owner::where('user_id', $user->id)->delete();
        }

        // 4. Delete the user
        $user->delete();

        // 5. Return response
        return response()->json([
            'message' => 'User deleted successfully'
        ], 200);
    }

}

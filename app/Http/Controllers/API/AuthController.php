<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

// ✅ Import requests and models
use App\Http\Requests\StoreUserRequest;
use App\Models\User;
use App\Models\Client;
use App\Models\Owner;

// ✅ Imports for email verification
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Carbon;
use App\Mail\VerifyEmailCustom;

class AuthController extends Controller
{
    /**
     * Handle user registration
     */
    public function register(StoreUserRequest $request)
    {
        // ✅ Validate the request using FormRequest
        $validated = $request->validated();

        // ✅ Accept only specific roles
        $validRoles = ['client', 'owner'];
        $role = in_array($validated['role'] ?? null, $validRoles) ? $validated['role'] : 'client';

        // ✅ Create the user
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $role,
            'phone_number' => $validated['phone_number'] ?? null,
            'national_id' => $validated['national_id'] ?? null,
            'location' => $validated['location'] ?? null,
        ]);

        // ✅ Create corresponding client or owner record
        if ($role === 'client') {
            Client::create(['user_id' => $user->id]);
        } elseif ($role === 'owner') {
            Owner::create(['user_id' => $user->id]);
        }

        // ✅ Generate signed URL for email verification (valid for 60 minutes)
        $verifyUrl = URL::temporarySignedRoute(
            'verification.verify', // route name
            Carbon::now()->addMinutes(60), // expiration
            ['id' => $user->id, 'hash' => sha1($user->email)] // parameters
        );

        // ✅ Send the custom email verification
        Mail::to($user->email)->send(new VerifyEmailCustom($user, $verifyUrl));

        return response()->json([
            'message' => 'Registered successfully. Please check your email for verification link.',
        ], 201);
    }

    /**
     * Handle user login
     */
    public function login(Request $request)
    {
        // ✅ Validate input
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // ✅ Find user by email
        $user = User::where('email', $request->email)->first();

        // ❌ If user not found or password incorrect
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid login details'], 401);
        }

        // ✅ Prevent login if email not verified
        if (!$user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Email not verified'
            ], 403);
        }

        // ✅ Optional: Limit token count to avoid abuse
        if ($user->tokens()->count() >= 5) {
            $user->tokens()->oldest()->first()->delete();
        }

        // ✅ Create access token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'data' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    /**
     * Handle user logout
     */
    public function logout(Request $request)
    {
        // ✅ Revoke current token
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out',
        ]);
    }
}

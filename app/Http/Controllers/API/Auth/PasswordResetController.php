<?php

namespace App\Http\Controllers\API\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Auth\Events\PasswordReset;

use Illuminate\Validation\ValidationException;
use App\Models\User;

class PasswordResetController extends Controller
{
    // إرسال رابط إعادة تعيين كلمة المرور
    public function sendResetLinkEmail(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        return $status === Password::RESET_LINK_SENT
                    ? response()->json(['message' => __($status)], 200)
                    : response()->json(['message' => __($status)], 400);
    }

    // إعادة تعيين كلمة المرور
    public function reset(Request $request)
    {
        $request->validate([
            'token'    => 'required',
            'email'    => 'required|email',
            'password' => 'required|min:6|confirmed',
        ], [
            'password.confirmed' => 'The password confirmation does not match.',
            'email.required'     => 'The email field is required.',
            'email.email'        => 'The email must be a valid email address.',
            'token.required'     => 'The token field is required.',
            'password.required'  => 'The password field is required.',
            'password.min'       => 'The password must be at least 6 characters.',
            'password.confirmed' => 'The password confirmation does not match.',

        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->password = bcrypt($password);
                $user->save();
            }
        );

        return $status === Password::PASSWORD_RESET
                    ? response()->json(['message' => __($status)])
                    : response()->json(['message' => __($status)], 400);
    }
}

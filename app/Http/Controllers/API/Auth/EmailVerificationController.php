<?php

namespace App\Http\Controllers\API\Auth;

// Import Mail and URL for email verification
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Carbon;
use Illuminate\Auth\Events\Verified;


// Import the custom email verification Mailable
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use App\Mail\VerifyEmailCustom;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;


class EmailVerificationController extends Controller
{
    /**
     * ✅ تأكيد البريد لما المستخدم يضغط على اللينك اللي في الإيميل
     */
    public function verify(Request $request)
    {
        $user = User::findOrFail($request->route('id'));

        if (!hash_equals((string) $request->route('hash'), sha1($user->getEmailForVerification()))) {
            return response()->json(['message' => 'Invalid verification link.'], 403);
        }

        if ($user->hasVerifiedEmail()) {
            // ✅ لو الإيميل مفعل بالفعل، نرجع توكن جديد
            $token = $user->createToken('auth_token')->plainTextToken;
            return redirect()->away(env('FRONTEND_URL') . "/email-verified?token={$token}");
        }

        $user->markEmailAsVerified();
        event(new Verified($user));

        // ✅ أنشئ توكن جديد بعد التفعيل
        $token = $user->createToken('auth_token')->plainTextToken;

        // ✅ أرجع للفرونت ومعاك التوكن
        return redirect()->away(env('FRONTEND_URL') . "/email-verified?token={$token}");
    }



    /**
     * ✅ إعادة إرسال رابط التفعيل في حالة عدم تأكيد البريد
     */
    public function resend(Request $request)
    {
        // لو المستخدم فعّل الإيميل بالفعل، ما فيش داعي نبعت تاني
        if ($request->user()->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified.']);
        }

        // إرسال رابط التفعيل
        $request->user()->sendEmailVerificationNotification();

        return response()->json(['message' => 'Verification link sent!']);
    }

    public function resendByEmail(Request $request)
    {
        // ✅ Validate email
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::where('email', $request->email)->first();

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified.'], 200);
        }

        // ✳️ استخدم Notification الأصلية
        // $user->sendEmailVerificationNotification();
        $verifyUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        Mail::to($user->email)->send(new VerifyEmailCustom($user, $verifyUrl));


        return response()->json(['message' => 'Verification email sent successfully.'], 200);
    }
}

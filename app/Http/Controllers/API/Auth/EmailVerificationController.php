<?php

namespace App\Http\Controllers\API\Auth;

// Import Mail and URL for email verification
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Carbon;

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
    public function verify(EmailVerificationRequest $request)
    {
        $request->fulfill(); // يقوم بتحديد verified_at للمستخدم

        // تسجيل الدخول تلقائيًا
        $user = $request->user();
        Auth::login($user); // مهم علشان نقدر ننشئ التوكن

        // إصدار التوكن
        $token = $user->createToken('bookshare-token')->plainTextToken;

        // تحويل للفرونت إند مع التوكن في URL
        return redirect(env('FRONTEND_URL') . '/email-verified?token=' . $token);
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

        $verifyUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        Mail::to($user->email)->send(new VerifyEmailCustom($user, $verifyUrl));

        return response()->json(['message' => 'Verification email sent successfully.'], 200);
    }
}

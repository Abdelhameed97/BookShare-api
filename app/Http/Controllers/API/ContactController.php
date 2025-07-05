<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\ContactMessage;
use Illuminate\Support\Facades\Validator;

class ContactController extends Controller
{
    public function send(Request $request)
    {
        // ✅ Validation
        $validator = Validator::make($request->all(), [
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ], 422);
        }

        // ✅ Prepare data
        $data = [
            'name' => $request->user()->name,
            'email' => $request->user()->email,
            'subject' => $request->subject,
            'message' => $request->message,
        ];

        // ✅ Send email
        Mail::to('abdelhameed7mohammed21@gmail.com')->send(new ContactMessage($data));

        return response()->json([
            'message' => 'Your message has been sent successfully.',
        ], 200);
    }
}

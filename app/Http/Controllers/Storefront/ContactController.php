<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Mail\ContactRequestMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class ContactController extends Controller
{
    public function show(): Response
    {
        return Inertia::render('Storefront/Contact', [
            'supportEmail' => config('blipblap.support_email'),
            'mailFrom' => config('mail.from.address'),
            'mailDriver' => config('mail.default'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'topic' => ['required', 'string', 'max:80'],
            'order_reference' => ['nullable', 'string', 'max:80'],
            'message' => ['required', 'string', 'min:10', 'max:3000'],
        ]);

        Mail::to((string) config('blipblap.support_email'))
            ->send(new ContactRequestMail($data));

        return back()->with('status', 'Thanks. Your message has been sent to BlipBlap support.');
    }
}

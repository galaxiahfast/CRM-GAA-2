<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class PasswordResetLinkController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email:rfc', 'max:255'],
        ]);

        $email = $validated['email'];
        $user = User::query()
            ->where('email', $email)
            ->get()
            ->first(fn (User $candidate): bool => hash_equals((string) $candidate->email, $email));

        if (! $user) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'El correo debe coincidir exactamente con una cuenta registrada.']);
        }

        $status = Password::sendResetLink(['email' => $user->email]);

        if ($status !== Password::RESET_LINK_SENT) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => __($status)]);
        }

        return back()->with('status', 'Te enviamos un enlace seguro para crear una nueva contraseña.');
    }
}

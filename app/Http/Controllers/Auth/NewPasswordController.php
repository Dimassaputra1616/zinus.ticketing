<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class NewPasswordController extends Controller
{
    private const VERIFIED_SESSION_MINUTES = 10;

    /**
     * Display the password reset view.
     */
    public function create(Request $request): View|RedirectResponse
    {
        $email = $this->verifiedEmail($request);

        if (! $email) {
            return redirect()
                ->route('password.request')
                ->withErrors(['email' => 'Verifikasi kode reset password terlebih dahulu.']);
        }

        return view('auth.reset-password', ['email' => $email]);
    }

    /**
     * Handle an incoming new password request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $email = $this->verifiedEmail($request);

        if (! $email || $email !== strtolower($validated['email'])) {
            return redirect()
                ->route('password.request')
                ->withErrors(['email' => 'Sesi reset password sudah berakhir. Minta kode baru.']);
        }

        $user = User::whereRaw('LOWER(email) = ?', [$email])->first();

        if (! $user) {
            $request->session()->forget('password_reset');

            return redirect()
                ->route('password.request')
                ->withErrors(['email' => 'Sesi reset password sudah berakhir. Minta kode baru.']);
        }

        $user->forceFill([
            'password' => Hash::make($validated['password']),
            'remember_token' => Str::random(60),
        ])->save();

        DB::table('password_reset_tokens')->where('email', $email)->delete();
        $request->session()->forget('password_reset');

        event(new PasswordReset($user));

        return redirect()
            ->route('login')
            ->with('status', 'Password berhasil direset. Silakan login dengan password baru.');
    }

    private function verifiedEmail(Request $request): ?string
    {
        $email = $request->session()->get('password_reset.email');
        $verifiedAt = $request->session()->get('password_reset.verified_at');

        if (! $email || ! $verifiedAt) {
            return null;
        }

        if (Carbon::parse($verifiedAt)->lt(now()->subMinutes(self::VERIFIED_SESSION_MINUTES))) {
            $request->session()->forget('password_reset');

            return null;
        }

        return strtolower($email);
    }
}

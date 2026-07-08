<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\PasswordResetCodeNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    private const CODE_EXPIRES_MINUTES = 10;
    private const RESEND_COOLDOWN_SECONDS = 60;
    private const MAX_ATTEMPTS = 5;

    /**
     * Display the password reset code request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset code request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $email = strtolower($validated['email']);
        $user = User::whereRaw('LOWER(email) = ?', [$email])->first();

        if ($user) {
            $existing = DB::table('password_reset_tokens')->where('email', $email)->first();
            $canSendCode = ! $existing
                || ! $existing->created_at
                || Carbon::parse($existing->created_at)->lte(now()->subSeconds(self::RESEND_COOLDOWN_SECONDS))
                || ($existing->expires_at && Carbon::parse($existing->expires_at)->isPast());

            if ($canSendCode) {
                $code = (string) random_int(100000, 999999);

                DB::table('password_reset_tokens')->updateOrInsert(
                    ['email' => $email],
                    [
                        'token' => Hash::make($code),
                        'attempts' => 0,
                        'created_at' => now(),
                        'expires_at' => now()->addMinutes(self::CODE_EXPIRES_MINUTES),
                    ],
                );

                $user->notify(new PasswordResetCodeNotification($code, self::CODE_EXPIRES_MINUTES));
            }
        }

        return redirect()
            ->route('password.code', ['email' => $email])
            ->with('status', 'Jika email terdaftar, kode verifikasi sudah dikirim.');
    }

    public function code(Request $request): View
    {
        return view('auth.reset-password-code', [
            'email' => old('email', (string) $request->query('email', '')),
        ]);
    }

    public function verifyCode(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'code' => ['required', 'digits:6'],
        ]);

        $email = strtolower($validated['email']);
        $record = DB::table('password_reset_tokens')->where('email', $email)->first();

        if (! $record) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['code' => 'Kode verifikasi tidak valid atau sudah kedaluwarsa.']);
        }

        if ($record->expires_at && Carbon::parse($record->expires_at)->isPast()) {
            DB::table('password_reset_tokens')->where('email', $email)->delete();

            return back()
                ->withInput($request->only('email'))
                ->withErrors(['code' => 'Kode verifikasi sudah kedaluwarsa. Minta kode baru.']);
        }

        if ((int) $record->attempts >= self::MAX_ATTEMPTS) {
            DB::table('password_reset_tokens')->where('email', $email)->delete();

            return back()
                ->withInput($request->only('email'))
                ->withErrors(['code' => 'Kode terlalu sering salah. Minta kode baru.']);
        }

        if (! Hash::check($validated['code'], $record->token)) {
            $attempts = (int) $record->attempts + 1;
            DB::table('password_reset_tokens')->where('email', $email)->update(['attempts' => $attempts]);

            if ($attempts >= self::MAX_ATTEMPTS) {
                DB::table('password_reset_tokens')->where('email', $email)->delete();

                return back()
                    ->withInput($request->only('email'))
                    ->withErrors(['code' => 'Kode terlalu sering salah. Minta kode baru.']);
            }

            return back()
                ->withInput($request->only('email'))
                ->withErrors(['code' => 'Kode verifikasi tidak valid.']);
        }

        $request->session()->put('password_reset.email', $email);
        $request->session()->put('password_reset.verified_at', now()->toIso8601String());

        return redirect()
            ->route('password.reset')
            ->with('status', 'Kode valid. Silakan buat password baru.');
    }
}

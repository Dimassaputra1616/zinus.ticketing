<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\PasswordResetCodeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_code_request_screen_can_be_rendered(): void
    {
        $response = $this->get('/forgot-password');

        $response
            ->assertStatus(200)
            ->assertSeeText('Back to login')
            ->assertSee('data-auth-card="compact"', false)
            ->assertSeeInOrder(['Back to login', 'Masuk ke Portal IT', 'Kirim kode reset password'])
            ->assertSee('href="'.route('login').'"', false)
            ->assertSeeText('Kirim Kode Verifikasi');
    }

    public function test_reset_password_code_can_be_requested(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email])
            ->assertRedirect(route('password.code', ['email' => $user->email]));

        Notification::assertSentTo($user, PasswordResetCodeNotification::class);

        $record = DB::table('password_reset_tokens')->where('email', $user->email)->first();

        $this->assertNotNull($record);
        $this->assertSame(0, (int) $record->attempts);
        $this->assertNotNull($record->expires_at);
    }

    public function test_reset_password_code_notification_uses_zinus_mail_template(): void
    {
        $user = User::factory()->make([
            'name' => 'Dimas Saputra',
            'email' => 'dimas@zinus.com',
        ]);

        $mail = (new PasswordResetCodeNotification('123456'))->toMail($user);

        $this->assertSame('Kode Reset Password Portal IT Zinus', $mail->subject);
        $this->assertSame([
            'html' => 'emails.auth.password-reset',
            'text' => 'emails.auth.password-reset-text',
        ], $mail->view);
        $this->assertSame('Dimas Saputra', $mail->viewData['displayName']);
        $this->assertSame('dimas@zinus.com', $mail->viewData['email']);
        $this->assertSame('123456', $mail->viewData['code']);
        $this->assertSame(10, $mail->viewData['expiresIn']);

        $this->view($mail->view['html'], $mail->viewData)
            ->assertSee('Kode reset password')
            ->assertSee('Verification Code')
            ->assertSee('123456')
            ->assertSee('Dimas Saputra');
    }

    public function test_reset_password_code_screen_can_be_rendered(): void
    {
        $this->get(route('password.code', ['email' => 'dimas@example.test']))
            ->assertOk()
            ->assertSeeText('Back to email')
            ->assertSee('data-auth-card="compact"', false)
            ->assertSeeInOrder(['Back to email', 'Masuk ke Portal IT', 'Masukkan kode email'])
            ->assertSeeText('Masukkan kode email')
            ->assertSee('value="dimas@example.test"', false);
    }

    public function test_reset_password_screen_requires_verified_code(): void
    {
        $this->get(route('password.reset'))
            ->assertRedirect(route('password.request'))
            ->assertSessionHasErrors('email');
    }

    public function test_password_can_be_reset_with_valid_code(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, PasswordResetCodeNotification::class, function ($notification) use ($user) {
            $this->post(route('password.code.verify'), [
                'email' => $user->email,
                'code' => $notification->code,
            ])->assertRedirect(route('password.reset'));

            $this->get(route('password.reset'))
                ->assertOk()
                ->assertSeeText('Back to code')
                ->assertSee('data-auth-card="compact"', false)
                ->assertSeeInOrder(['Back to code', 'Masuk ke Portal IT', 'Buat password baru'])
                ->assertSee('value="'.$user->email.'"', false);

            $this->post(route('password.store'), [
                'email' => $user->email,
                'password' => 'new-password-123',
                'password_confirmation' => 'new-password-123',
            ])
                ->assertSessionHasNoErrors()
                ->assertRedirect(route('login'));

            $user->refresh();

            $this->assertTrue(Hash::check('new-password-123', $user->password));
            $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);

            return true;
        });
    }

    public function test_invalid_reset_code_increments_attempts(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, PasswordResetCodeNotification::class, function () use ($user) {
            $this->post(route('password.code.verify'), [
                'email' => $user->email,
                'code' => '000000',
            ])->assertSessionHasErrors('code');

            $record = DB::table('password_reset_tokens')->where('email', $user->email)->first();

            $this->assertSame(1, (int) $record->attempts);

            return true;
        });
    }
}

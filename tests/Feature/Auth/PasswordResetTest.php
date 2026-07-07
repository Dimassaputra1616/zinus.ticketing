<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_link_screen_can_be_rendered(): void
    {
        $response = $this->get('/forgot-password');

        $response
            ->assertStatus(200)
            ->assertSeeText(__('messages.back_to_login'))
            ->assertSee('href="'.route('login').'"', false)
            ->assertSeeText(__('messages.send_reset_link'));
    }

    public function test_reset_password_link_can_be_requested(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_reset_password_notification_uses_zinus_mail_template(): void
    {
        $user = User::factory()->make([
            'name' => 'Dimas Saputra',
            'email' => 'dimas@zinus.com',
        ]);

        $mail = (new ResetPassword('sample-token'))->toMail($user);

        $this->assertSame('Reset Password Portal IT Zinus', $mail->subject);
        $this->assertSame([
            'html' => 'emails.auth.password-reset',
            'text' => 'emails.auth.password-reset-text',
        ], $mail->view);
        $this->assertSame('Dimas Saputra', $mail->viewData['displayName']);
        $this->assertSame('dimas@zinus.com', $mail->viewData['email']);
        $this->assertStringContainsString('/reset-password/sample-token', $mail->viewData['resetUrl']);
        $this->assertStringContainsString('email=dimas%40zinus.com', $mail->viewData['resetUrl']);
        $this->assertSame(60, $mail->viewData['expiresIn']);

        $this->view($mail->view['html'], $mail->viewData)
            ->assertSee('Reset password akun Anda')
            ->assertSee('Password Recovery')
            ->assertSee('Dimas Saputra');
    }

    public function test_reset_password_screen_can_be_rendered(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) {
            $response = $this->get('/reset-password/'.$notification->token);

            $response->assertStatus(200);

            return true;
        });
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $response = $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'password',
                'password_confirmation' => 'password',
            ]);

            $response
                ->assertSessionHasNoErrors()
                ->assertRedirect(route('login'));

            return true;
        });
    }
}

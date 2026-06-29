<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Enums\VendorApprovalStatus;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_customer_registration_creates_account_profile_token_and_verification_notification(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/auth/register/customer', [
            'name' => 'Test Customer',
            'email' => 'customer@example.com',
            'phone' => '+9779800000001',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Customer registration successful.')
            ->assertJsonPath('user.role', UserRole::Customer->value)
            ->assertJsonPath('user.email', 'customer@example.com');

        $user = User::query()->where('email', 'customer@example.com')->firstOrFail();

        $this->assertNotNull($user->customerProfile);
        $this->assertNull($user->fresh()->email_verified_at);
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_vendor_registration_creates_pending_vendor_profile_and_token(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/auth/register/vendor', [
            'name' => 'Vendor Owner',
            'email' => 'vendor@example.com',
            'phone' => '+9779800000002',
            'password' => 'password',
            'password_confirmation' => 'password',
            'store_name' => 'Acme Store',
            'city' => 'Kathmandu',
            'district' => 'Kathmandu',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Vendor registration successful.')
            ->assertJsonPath('user.role', UserRole::Vendor->value)
            ->assertJsonPath('user.vendor_profile.approval_status', VendorApprovalStatus::Pending->value);

        $user = User::query()->where('email', 'vendor@example.com')->firstOrFail();

        $this->assertSame(VendorApprovalStatus::Pending, $user->vendorProfile->approval_status);
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_login_me_and_logout_work_with_sanctum_tokens(): void
    {
        $user = User::factory()->customer()->create([
            'email' => 'login@example.com',
            'password' => 'password',
        ]);

        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => 'login@example.com',
            'password' => 'password',
            'device_name' => 'feature-test',
        ]);

        $token = $loginResponse->json('token');

        $loginResponse
            ->assertOk()
            ->assertJsonPath('message', 'Login successful.')
            ->assertJsonPath('user.id', $user->id);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('id', $user->id)
            ->assertJsonPath('role', UserRole::Customer->value);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/auth/logout')
            ->assertOk()
            ->assertJsonPath('message', 'Logged out successfully.');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_admin_can_login_using_bootstrap_credentials(): void
    {
        $this->seed(AdminUserSeeder::class);

        $this->postJson('/api/auth/login', [
            'email' => 'admin@multi-vendor.local',
            'password' => 'password',
        ])
            ->assertOk()
            ->assertJsonPath('user.role', UserRole::Admin->value);
    }

    public function test_forgot_password_sends_reset_notification_and_reset_password_updates_credentials(): void
    {
        Notification::fake();

        $user = User::factory()->customer()->create([
            'email' => 'reset@example.com',
            'password' => 'password',
        ]);

        $this->postJson('/api/auth/forgot-password', [
            'email' => $user->email,
        ])
            ->assertOk()
            ->assertJsonPath('message', 'Password reset link sent successfully.');

        Notification::assertSentTo($user, ResetPassword::class);

        $token = Password::broker()->createToken($user);

        $this->postJson('/api/auth/reset-password', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])
            ->assertOk()
            ->assertJsonPath('message', 'Password reset successfully.');

        $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'new-password',
        ])
            ->assertOk()
            ->assertJsonPath('user.id', $user->id);
    }

    public function test_email_verification_and_resend_flow_work(): void
    {
        Notification::fake();

        $user = User::factory()->customer()->unverified()->create();

        $token = $user->createToken('verification-test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/auth/email/verification-notification')
            ->assertOk()
            ->assertJsonPath('message', 'Verification link sent successfully.');

        Notification::assertSentTo($user, VerifyEmail::class);

        $verificationUrl = \URL::temporarySignedRoute(
            'auth.verification.verify',
            now()->addMinutes(60),
            [
                'user' => $user->id,
                'hash' => sha1($user->email),
            ]
        );

        $this->getJson($verificationUrl)
            ->assertOk()
            ->assertJsonPath('message', 'Email address verified successfully.');

        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_customer_routes_require_customer_role_and_json_auth_errors(): void
    {
        $this->getJson('/api/customer/profile')
            ->assertUnauthorized()
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);

        $vendor = User::factory()->vendor()->create();
        $token = $vendor->createToken('vendor')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/customer/profile')
            ->assertForbidden()
            ->assertJson([
                'message' => 'This action is unauthorized.',
            ]);
    }
}

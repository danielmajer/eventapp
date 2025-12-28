<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AuditLogService;
use App\Services\FieldEncryptionService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Find user by email, handling encrypted email fields
     *
     * @param  string  $email
     * @return User|null
     */
    protected function findUserByEmail(string $email): ?User
    {
        // Since email is encrypted in the database, we need to:
        // 1. Try to encrypt the input email and query directly (if encryption is working)
        // 2. Or get all users and compare decrypted emails

        try {
            // First, try to encrypt the email and query
            $encryptedEmail = FieldEncryptionService::encrypt($email);
            $user = User::where('email', $encryptedEmail)->first();

            if ($user) {
                return $user;
            }
        } catch (\Exception $e) {
            // Encryption might not be configured, fall back to decryption method
        }

        // Fallback: Get all users and compare decrypted emails
        // This is less efficient but works when encryption/decryption is handled by the trait
        $users = User::all();
        foreach ($users as $u) {
            // The email is automatically decrypted by the EncryptsFields trait on retrieval
            if ($u->email === $email) {
                return $u;
            }
        }

        return null;
    }

    /**
     * Get GoogleAuthenticator instance, handling different possible class names
     */
    protected function getGoogleAuthenticator()
    {
        // Try different possible class names (the package uses PHPGangsta_GoogleAuthenticator)
        if (class_exists('PHPGangsta_GoogleAuthenticator')) {
            return new \PHPGangsta_GoogleAuthenticator();
        } elseif (class_exists('PHPGangsta\GoogleAuthenticator\GoogleAuthenticator')) {
            return new \PHPGangsta\GoogleAuthenticator\GoogleAuthenticator();
        } else {
            throw new \Exception('GoogleAuthenticator library not installed. Run: composer require phpgangsta/googleauthenticator:dev-master');
        }
    }
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        // Manually verify credentials to avoid guard / provider misconfiguration issues
        // Since email is encrypted, we need to find user by comparing encrypted email
        $user = $this->findUserByEmail($credentials['email']);
        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            // Log failed login attempt
            AuditLogService::logAuth('login_failed', null, [
                'email' => $credentials['email'],
                'ip' => $request->ip(),
            ]);
            return response()->json(['message' => 'Invalid credentials'], Response::HTTP_UNAUTHORIZED);
        }


        // Log the user into the default guard (optional but keeps Auth::user() working)
        Auth::login($user);

        // Optional: check if MFA is enabled and require verification
        if ($user->mfa_enabled ?? false) {
            // Log MFA required
            AuditLogService::logAuth('login_mfa_required', $user);
            // In a real implementation, issue an intermediate token and ask for MFA code
            return response()->json([
                'requires_mfa' => true,
            ], Response::HTTP_OK);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        // Log successful login
        AuditLogService::logAuth('login_success', $user, [
            'token_created' => true,
        ]);

        return response()->json([
            'token' => $token,
            'user' => $user,
        ]);
    }

    public function logout(Request $request)
    {
        $user = $request->user();

        // Log logout
        AuditLogService::logAuth('logout', $user);

        $user->currentAccessToken()->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    public function sendPasswordResetLink(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        // Find user by encrypted email
        $user = $this->findUserByEmail($request->input('email'));

        // Don't reveal if user exists or not (security best practice)
        if (!$user) {
            // Still return success to prevent email enumeration
            return response()->json([
                'message' => 'If that email address exists, we have sent a password reset link.',
                'reset_link' => null,
            ]);
        }

        // Generate password reset token manually
        $token = Str::random(64);

        // Store token in password_reset_tokens table
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            [
                'token' => Hash::make($token),
                'created_at' => now(),
            ]
        );

        // Generate reset link URL
        $frontendUrl = config('app.frontend_url', 'http://localhost:5173');
        $resetLink = "{$frontendUrl}/password/reset?token={$token}&email=" . urlencode($user->email);

        // Log password reset request
        AuditLogService::logAuth('password_reset_requested', $user, [
            'reset_link_generated' => true,
        ]);

        return response()->json([
            'message' => 'Password reset link generated successfully.',
            'reset_link' => $resetLink,
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // Find user by encrypted email
        $user = $this->findUserByEmail($request->input('email'));

        if (!$user) {
            // Don't reveal if user exists
            return response()->json(['message' => 'Invalid reset token or email.'], Response::HTTP_BAD_REQUEST);
        }

        // Use the user's decrypted email for password reset
        $resetData = [
            'email' => $user->email, // Use decrypted email
            'password' => $request->input('password'),
            'password_confirmation' => $request->input('password_confirmation'),
            'token' => $request->input('token'),
        ];

        // Verify token manually
        $tokenRecord = DB::table('password_reset_tokens')
            ->where('email', $user->email)
            ->first();

        if (!$tokenRecord || !Hash::check($request->input('token'), $tokenRecord->token)) {
            AuditLogService::logAuth('password_reset_failed', $user, [
                'reason' => 'Invalid or expired token',
            ]);
            return response()->json(['message' => 'Invalid or expired reset token.'], Response::HTTP_BAD_REQUEST);
        }

        // Check if token is expired (60 minutes)
        $tokenAge = now()->diffInMinutes($tokenRecord->created_at);
        if ($tokenAge > 60) {
            DB::table('password_reset_tokens')->where('email', $user->email)->delete();
            AuditLogService::logAuth('password_reset_failed', $user, [
                'reason' => 'Token expired',
            ]);
            return response()->json(['message' => 'Reset token has expired. Please request a new one.'], Response::HTTP_BAD_REQUEST);
        }

        // Reset password
        $user->forceFill([
            'password' => Hash::make($request->input('password')),
            'remember_token' => Str::random(60),
        ])->save();

        // Delete the token (single-use)
        DB::table('password_reset_tokens')->where('email', $user->email)->delete();

        event(new PasswordReset($user));

        // Log password reset
        AuditLogService::logAuth('password_reset_completed', $user);

        $status = Password::PASSWORD_RESET;

        return response()->json(['message' => 'Password has been reset successfully.']);
    }

    // MFA setup with Google Authenticator
    public function setupMfa(Request $request)
    {
        $user = $request->user();

        $ga = $this->getGoogleAuthenticator();

        // Generate a new secret if user doesn't have one
        if (!$user->mfa_secret) {
            $secret = $ga->createSecret();
            $user->mfa_secret = $secret;
            $user->save();
        } else {
            $secret = $user->mfa_secret;
        }

        // Generate QR code URL
        // Method signature: getQRCodeGoogleUrl($name, $secret, $title = null, $params = array())
        // $name = label/identifier (usually email or username)
        // $secret = the secret key
        // $title = issuer name (app name)
        $appName = config('app.name', 'Event Manager');

        // Build proper otpauth:// URL manually to ensure correct format
        $otpauthUrl = 'otpauth://totp/' . rawurlencode($appName . ':' . $user->email) . '?secret=' . $secret . '&issuer=' . rawurlencode($appName);

        // Generate QR code using Google Charts API
        $qrCodeUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($otpauthUrl);

        return response()->json([
            'message' => 'MFA setup initiated. Scan the QR code with your authenticator app.',
            'secret' => $secret, // For manual entry if QR code doesn't work
            'qr_code_url' => $qrCodeUrl,
            'user' => $user->fresh(),
        ]);
    }

    // Confirm MFA setup by verifying a code
    public function confirmMfaSetup(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $user = $request->user();

        if (!$user->mfa_secret) {
            return response()->json(['message' => 'MFA secret not found. Please set up MFA first.'], Response::HTTP_BAD_REQUEST);
        }

        $ga = $this->getGoogleAuthenticator();
        $isValid = $ga->verifyCode($user->mfa_secret, $request->input('code'), 2); // 2 = 60 second window

        if (!$isValid) {
            return response()->json(['message' => 'Invalid code. Please try again.'], Response::HTTP_BAD_REQUEST);
        }

        // Enable MFA after successful verification
        $user->mfa_enabled = true;
        $user->save();

        // Log MFA enabled
        AuditLogService::logAuth('mfa_enabled', $user);

        return response()->json([
            'message' => 'MFA enabled successfully',
            'user' => $user->fresh(),
        ]);
    }

    public function disableMfa(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $user = $request->user();

        if (!$user->mfa_secret) {
            return response()->json(['message' => 'MFA is not set up for this account.'], Response::HTTP_BAD_REQUEST);
        }

        // Verify code before disabling
        $ga = $this->getGoogleAuthenticator();
        $isValid = $ga->verifyCode($user->mfa_secret, $request->input('code'), 2);

        if (!$isValid) {
            return response()->json(['message' => 'Invalid code. Please try again.'], Response::HTTP_BAD_REQUEST);
        }

        $user->mfa_enabled = false;
        $user->mfa_secret = null; // Optionally clear the secret
        $user->save();

        // Log MFA disabled
        AuditLogService::logAuth('mfa_disabled', $user);

        return response()->json([
            'message' => 'MFA disabled successfully',
            'user' => $user->fresh(),
        ]);
    }

    public function verifyMfa(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|string|size:6',
        ]);

        $user = $this->findUserByEmail($request->input('email'));
        if (!$user) {
            return response()->json(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        if (!($user->mfa_enabled ?? false)) {
            return response()->json(['message' => 'MFA is not enabled for this user'], Response::HTTP_BAD_REQUEST);
        }

        if (!$user->mfa_secret) {
            return response()->json(['message' => 'MFA secret not found'], Response::HTTP_BAD_REQUEST);
        }

        $ga = $this->getGoogleAuthenticator();
        $isValid = $ga->verifyCode($user->mfa_secret, $request->input('code'), 2); // 2 = 60 second window

        if (!$isValid) {
            // Log failed MFA verification
            AuditLogService::logAuth('mfa_verification_failed', $user, [
                'email' => $request->input('email'),
            ]);
            return response()->json(['message' => 'Invalid MFA code'], Response::HTTP_UNAUTHORIZED);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        // Log successful MFA verification and login
        AuditLogService::logAuth('mfa_verification_success', $user, [
            'token_created' => true,
        ]);

        return response()->json([
            'token' => $token,
            'user' => $user,
        ]);
    }
}



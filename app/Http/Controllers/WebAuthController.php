<?php

namespace App\Http\Controllers;

use App\Actions\Auth\VerifyGoogleIdTokenAction;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class WebAuthController extends Controller
{
    /**
     * Show the login page.
     */
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('login');
    }

    /**
     * Handle the Google sign-in ID token validation and authenticate the session.
     */
    public function handleGoogleCallback(
        Request $request,
        VerifyGoogleIdTokenAction $verifyGoogleIdToken,
    ): JsonResponse {
        $idToken = $request->input('id_token');

        if (! $idToken) {
            return response()->json([
                'success' => false,
                'message' => 'Google ID token is required.',
            ], 400);
        }

        try {
            // Verify Google ID Token
            $data = $verifyGoogleIdToken->execute($idToken);

            $user = User::query()
                ->where('google_id', $data['google_id'])
                ->orWhere('email', $data['email'])
                ->first();

            if (! $user) {
                $user = new User();
            }

            // Sync user data
            $user->name = $data['name'] ?: $user->name ?: 'Google User';
            $user->email = $data['email'];
            $user->google_id = $data['google_id'];
            $user->avatar_url = $data['avatar_url'];
            $user->last_login_at = now();

            if ($data['email_verified'] && is_null($user->email_verified_at)) {
                $user->email_verified_at = now();
            }

            if (empty($user->password)) {
                $user->password = Str::password(32);
            }

            $user->save();

            // Authenticate the user session
            Auth::login($user, remember: true);

            // Security: Regenerate the session ID to prevent Session Fixation attacks
            $request->session()->regenerate();

            return response()->json([
                'success' => true,
                'redirect_url' => route('dashboard'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication failed: ' . $e->getMessage(),
            ], 401);
        }
    }

    /**
     * Log the user out of the application.
     */
    public function logout(Request $request): RedirectResponse
    {
        // Authenticate logout on server-side
        Auth::logout();

        // Invalidate active session and regenerate CSRF token to prevent replay/hijacking
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}

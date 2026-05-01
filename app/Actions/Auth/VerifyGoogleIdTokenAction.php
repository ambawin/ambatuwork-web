<?php

namespace App\Actions\Auth;

use Google\Client as GoogleClient;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Throwable;

class VerifyGoogleIdTokenAction
{
    public function execute(string $idToken): array
    {
        $clientId = config('services.google.web_client_id');

        if (! $clientId) {
            throw ValidationException::withMessages([
                'id_token' => ['Google client configuration is missing.'],
            ]);
        }

        try {
            $client = new GoogleClient([
                'client_id' => $clientId,
            ]);

            $payload = $client->verifyIdToken($idToken);
        } catch (Throwable $e) {
            throw ValidationException::withMessages([
                'id_token' => ['Invalid Google ID token.'],
            ]);
        }

        if (! $payload) {
            throw ValidationException::withMessages([
                'id_token' => ['Invalid Google ID token.'],
            ]);
        }

        $googleId = Arr::get($payload, 'sub');
        $email = Arr::get($payload, 'email');
        $name = Arr::get($payload, 'name');
        $avatarUrl = Arr::get($payload, 'picture');
        $emailVerified = (bool) Arr::get($payload, 'email_verified', false);

        if (! $googleId || ! $email) {
            throw ValidationException::withMessages([
                'id_token' => ['Google token payload is incomplete.'],
            ]);
        }

        return [
            'google_id' => $googleId,
            'email' => $email,
            'name' => $name ?: 'Google User',
            'avatar_url' => $avatarUrl,
            'email_verified' => $emailVerified,
            'raw_payload' => $payload,
        ];
    }
}
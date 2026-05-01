<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Auth\VerifyGoogleIdTokenAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\GoogleAuthRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function google(
        GoogleAuthRequest $request,
        VerifyGoogleIdTokenAction $verifyGoogleIdToken,
    ): JsonResponse {
        $data = $verifyGoogleIdToken->execute($request->string('id_token')->toString());

        $user = User::query()
            ->where('google_id', $data['google_id'])
            ->orWhere('email', $data['email'])
            ->first();

        if (! $user) {
            $user = new User();
        }

        $user->name = $data['name'] ?: $user->name;
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

        $deviceName = $request->string('device_name')->toString() ?: 'android';

        $token = $user->createToken($deviceName)->plainTextToken;

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => new UserResource($user),
        ]);
    }

    public function me(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }
}
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

use OpenApi\Attributes as OA;

class AuthController extends Controller
{
    #[OA\Post(
        path: '/auth/google',
        summary: 'Exchange Google ID Token for API Token',
        description: 'Exchanges a Google ID token for a Sanctum API token. If the user doesn\'t exist, it creates a new user.',
        tags: ['Authentication'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['id_token'],
                properties: [
                    new OA\Property(property: 'id_token', type: 'string', description: 'Google ID Token', example: 'google-id-token-xyz'),
                    new OA\Property(property: 'device_name', type: 'string', description: 'Device/client identifier', default: 'android', example: 'android_client')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful Authentication',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'token', type: 'string', example: '1|sanctum-token-example'),
                        new OA\Property(property: 'token_type', type: 'string', example: 'Bearer'),
                        new OA\Property(property: 'user', ref: '#/components/schemas/User')
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Unprocessable Content / Validation failure')
        ]
    )]
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

    #[OA\Get(
        path: '/auth/me',
        summary: 'Get Authenticated User',
        description: 'Returns the currently logged in user\'s details.',
        tags: ['Authentication'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Authenticated User Data',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/User')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated')
        ]
    )]
    public function me(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    #[OA\Post(
        path: '/auth/logout',
        summary: 'Logout Authenticated User',
        description: 'Revokes and deletes the current Sanctum token.',
        tags: ['Authentication'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successfully logged out',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Logged out successfully.')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated')
        ]
    )]
    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }
}
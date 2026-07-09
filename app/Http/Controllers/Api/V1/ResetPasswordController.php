<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;

class ResetPasswordController extends Controller
{
    /**
     * POST /api/v1/auth/password/reset
     *
     * Reset the user's password using the token sent to their email.
     * On success, revokes all existing Sanctum tokens so the user must
     * log in again on all devices — standard security practice.
     */
    #[OA\Post(
        path: '/api/v1/auth/password/reset',
        summary: 'Reset user password using the token from the reset email',
        description: 'On success, all existing Sanctum tokens are revoked so the user must log in again on all devices.',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['token', 'email', 'password', 'password_confirmation'],
                properties: [
                    new OA\Property(property: 'token', type: 'string', example: 'abc123resettoken'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'test@exampletest.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'newSecret123'),
                    new OA\Property(property: 'password_confirmation', type: 'string', format: 'password', example: 'newSecret123'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Password reset successfully',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Password has been reset successfully. Please log in again.')
                    ], type: 'object')
                ])
            ),
            new OA\Response(response: 422, description: 'Invalid token or validation error'),
        ]
    )]
    public function reset(Request $request): JsonResponse
    {
        $request->validate([
            'token'                 => ['required', 'string'],
            'email'                 => ['required', 'email'],
            'password'              => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password'       => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                // Revoke all Sanctum tokens — user must log in on all devices
                $user->tokens()->delete();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'data' => ['message' => 'Password has been reset successfully. Please log in again.'],
            ]);
        }

        throw ValidationException::withMessages([
            'email' => [__($status)],
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use OpenApi\Attributes as OA;

class ForgotPasswordController extends Controller
{
    /**
     * POST /api/v1/auth/password/forgot
     *
     * Send a password reset link to the given email address.
     * Always returns 200 to avoid revealing whether an email exists in the system.
     */
    #[OA\Post(
        path: '/api/v1/auth/password/forgot',
        summary: 'Send a password reset link to the given email address',
        description: 'Always returns 200 to avoid revealing whether the email exists in the system (prevents user enumeration).',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'test@exampletest.com'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Reset link sent (or silently ignored if email not found)',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'If an account with that email exists, a password reset link has been sent.')
                    ], type: 'object')
                ])
            ),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function sendResetLink(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        // Password::sendResetLink() returns a status string regardless of
        // whether the email is found — this prevents user enumeration.
        Password::sendResetLink($request->only('email'));

        return response()->json([
            'data' => [
                'message' => 'If an account with that email exists, a password reset link has been sent.',
            ],
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAttachmentRequest;
use App\Http\Resources\AttachmentResource;
use App\Models\Attachment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use OpenApi\Attributes as OA;

class AttachmentController extends Controller
{
    #[OA\Post(
        path: '/api/v1/attachments',
        summary: 'Upload a file and attach it to a transaction',
        description: 'Accepted formats: jpg, jpeg, png, gif, webp, pdf. Maximum file size: 5 MB.',
        tags: ['Attachments'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['transaction_id', 'file'],
                    properties: [
                        new OA\Property(property: 'transaction_id', type: 'integer', example: 1),
                        new OA\Property(property: 'file', type: 'string', format: 'binary'),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Attachment uploaded'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error (wrong format or size)'),
        ]
    )]
    public function store(StoreAttachmentRequest $request): JsonResponse
    {
        $file = $request->file('file');

        // Store file in attachments subdirectory, auto-generates unique filename
        $path = $file->store('attachments', 'public');

        $attachment = Attachment::create([
            'transaction_id' => $request->transaction_id,
            'file_name'      => $file->getClientOriginalName(),
            'mime'           => $file->getMimeType(),
            'size'           => $file->getSize(),
            'path'           => $path,
        ]);

        return (new AttachmentResource($attachment))->response()->setStatusCode(201);
    }

    #[OA\Delete(
        path: '/api/v1/attachments/{id}',
        summary: 'Delete an attachment and remove the file from storage',
        tags: ['Attachments'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Attachment deleted'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ]
    )]
    public function destroy(Attachment $attachment): Response
    {
        // Ensure the attachment belongs to the authenticated user via the transaction
        $this->authorize('delete', $attachment);

        // Remove the file from storage before deleting the DB record
        Storage::disk('public')->delete($attachment->path);

        $attachment->delete();

        return response()->noContent();
    }
}

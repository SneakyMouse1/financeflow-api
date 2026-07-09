<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTagRequest;
use App\Http\Requests\UpdateTagRequest;
use App\Http\Resources\TagResource;
use App\Models\Tag;
use App\Services\TagService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use OpenApi\Attributes as OA;

class TagController extends Controller
{
    public function __construct(private readonly TagService $service) {}

    #[OA\Get(
        path: '/api/v1/tags',
        summary: 'Get all tags for the authenticated user',
        tags: ['Tags'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'List of tags'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Tag::class);
        $tags = $this->service->getAll(auth()->user());
        return TagResource::collection($tags);
    }

    #[OA\Post(
        path: '/api/v1/tags',
        summary: 'Create a new tag',
        tags: ['Tags'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'food'),
                    new OA\Property(property: 'color', type: 'string', nullable: true, example: '#EF4444'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Tag created'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(StoreTagRequest $request): JsonResponse
    {
        $this->authorize('create', Tag::class);
        $tag = $this->service->create(auth()->user(), $request->validated());
        return (new TagResource($tag))->response()->setStatusCode(201);
    }

    #[OA\Get(
        path: '/api/v1/tags/{id}',
        summary: 'Get a specific tag by ID',
        tags: ['Tags'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Tag details'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function show(Tag $tag): TagResource
    {
        $this->authorize('view', $tag);
        return new TagResource($tag);
    }

    #[OA\Patch(
        path: '/api/v1/tags/{id}',
        summary: 'Update a tag name or color',
        tags: ['Tags'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(properties: [
                new OA\Property(property: 'name', type: 'string', example: 'transport'),
                new OA\Property(property: 'color', type: 'string', nullable: true, example: '#3B82F6'),
            ])
        ),
        responses: [
            new OA\Response(response: 200, description: 'Tag updated'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(UpdateTagRequest $request, Tag $tag): TagResource
    {
        $this->authorize('update', $tag);
        $tag = $this->service->update($tag, $request->validated());
        return new TagResource($tag);
    }

    #[OA\Delete(
        path: '/api/v1/tags/{id}',
        summary: 'Delete a tag',
        tags: ['Tags'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Tag deleted'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ]
    )]
    public function destroy(Tag $tag): Response
    {
        $this->authorize('delete', $tag);
        $this->service->delete($tag);
        return response()->noContent();
    }
}

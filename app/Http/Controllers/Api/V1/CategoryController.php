<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

class CategoryController extends Controller
{
    public function __construct(private readonly CategoryService $service) {}

    #[OA\Get(
        path: '/api/v1/categories',
        summary: 'Get all categories for the authenticated user',
        tags: ['Categories'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'List of categories'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Category::class);
        $categories = $this->service->getAll(auth()->user());
        return CategoryResource::collection($categories);
    }

    #[OA\Post(
        path: '/api/v1/categories',
        summary: 'Create a new category',
        tags: ['Categories'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'type'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Groceries'),
                    new OA\Property(property: 'type', type: 'string', enum: ['income', 'expense'], example: 'expense'),
                    new OA\Property(property: 'color', type: 'string', nullable: true, example: '#F59E0B'),
                    new OA\Property(property: 'icon', type: 'string', nullable: true, example: 'shopping-cart'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Category created'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $this->authorize('create', Category::class);
        $category = $this->service->create(auth()->user(), $request->validated());
        return (new CategoryResource($category))->response()->setStatusCode(201);
    }

    #[OA\Get(
        path: '/api/v1/categories/{id}',
        summary: 'Get a specific category by ID',
        tags: ['Categories'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Category details'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function show(Category $category): CategoryResource
    {
        $this->authorize('view', $category);
        return new CategoryResource($category);
    }

    #[OA\Patch(
        path: '/api/v1/categories/{id}',
        summary: 'Update a specific category',
        tags: ['Categories'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(properties: [
                new OA\Property(property: 'name', type: 'string', example: 'Updated Category'),
                new OA\Property(property: 'type', type: 'string', enum: ['income', 'expense'], example: 'expense'),
                new OA\Property(property: 'color', type: 'string', nullable: true, example: '#6366F1'),
                new OA\Property(property: 'icon', type: 'string', nullable: true, example: 'tag'),
            ])
        ),
        responses: [
            new OA\Response(response: 200, description: 'Category updated'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(UpdateCategoryRequest $request, Category $category): CategoryResource
    {
        $this->authorize('update', $category);
        $category = $this->service->update($category, $request->validated());
        return new CategoryResource($category);
    }

    #[OA\Delete(
        path: '/api/v1/categories/{id}',
        summary: 'Delete a category (cannot delete default categories)',
        tags: ['Categories'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Category deleted'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden — default categories cannot be deleted'),
        ]
    )]
    public function destroy(Category $category): JsonResponse
    {
        $this->authorize('delete', $category);
        $this->service->delete($category);
        return response()->json(['data' => ['message' => 'Category deleted.']], 204);
    }
}

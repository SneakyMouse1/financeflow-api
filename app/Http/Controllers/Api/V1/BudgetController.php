<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBudgetRequest;
use App\Http\Requests\UpdateBudgetRequest;
use App\Http\Resources\BudgetResource;
use App\Models\Budget;
use App\Services\BudgetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use OpenApi\Attributes as OA;

class BudgetController extends Controller
{
    public function __construct(private readonly BudgetService $service) {}

    #[OA\Get(
        path: '/api/v1/budgets',
        summary: 'Get all budgets for the authenticated user',
        tags: ['Budgets'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'List of budgets with spent/remaining/progress'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Budget::class);
        $budgets = $this->service->getAll(auth()->user());
        return BudgetResource::collection($budgets);
    }

    #[OA\Post(
        path: '/api/v1/budgets',
        summary: 'Create a new budget for a category',
        tags: ['Budgets'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['category_id', 'period', 'amount', 'currency_code'],
                properties: [
                    new OA\Property(property: 'category_id', type: 'integer', example: 2),
                    new OA\Property(property: 'period', type: 'string', enum: ['monthly', 'yearly'], example: 'monthly'),
                    new OA\Property(property: 'amount', type: 'number', format: 'float', example: 500.00),
                    new OA\Property(property: 'currency_code', type: 'string', example: 'USD'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Budget created'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(StoreBudgetRequest $request): JsonResponse
    {
        $this->authorize('create', Budget::class);
        $budget = $this->service->create(auth()->user(), $request->validated());
        return (new BudgetResource($budget))->response()->setStatusCode(201);
    }

    #[OA\Get(
        path: '/api/v1/budgets/{id}',
        summary: 'Get a specific budget with current spending progress',
        tags: ['Budgets'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Budget details with spent/remaining/progress_percentage'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function show(Budget $budget): BudgetResource
    {
        $this->authorize('view', $budget);
        return new BudgetResource($this->service->show($budget));
    }

    #[OA\Patch(
        path: '/api/v1/budgets/{id}',
        summary: 'Update a specific budget',
        tags: ['Budgets'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(properties: [
                new OA\Property(property: 'amount', type: 'number', format: 'float', example: 750.00),
                new OA\Property(property: 'period', type: 'string', enum: ['monthly', 'yearly'], example: 'yearly'),
                new OA\Property(property: 'currency_code', type: 'string', example: 'EUR'),
            ])
        ),
        responses: [
            new OA\Response(response: 200, description: 'Budget updated'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(UpdateBudgetRequest $request, Budget $budget): BudgetResource
    {
        $this->authorize('update', $budget);
        $budget = $this->service->update($budget, $request->validated());
        return new BudgetResource($this->service->show($budget));
    }

    #[OA\Delete(
        path: '/api/v1/budgets/{id}',
        summary: 'Delete a budget',
        tags: ['Budgets'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Budget deleted'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ]
    )]
    public function destroy(Budget $budget): Response
    {
        $this->authorize('delete', $budget);
        $this->service->delete($budget);
        return response()->noContent();
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRecurringTransactionRequest;
use App\Http\Requests\UpdateRecurringTransactionRequest;
use App\Http\Resources\RecurringTransactionResource;
use App\Models\RecurringTransaction;
use App\Services\RecurringTransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use OpenApi\Attributes as OA;

class RecurringTransactionController extends Controller
{
    public function __construct(private readonly RecurringTransactionService $service) {}

    #[OA\Get(
        path: '/api/v1/recurring-transactions',
        summary: 'Get all recurring transactions for the authenticated user',
        tags: ['Recurring Transactions'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'List of recurring transactions'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', RecurringTransaction::class);
        $recurring = $this->service->getAll(auth()->user());

        return RecurringTransactionResource::collection($recurring);
    }

    #[OA\Post(
        path: '/api/v1/recurring-transactions',
        summary: 'Create a new recurring transaction template',
        description: 'Transfers are not supported for recurring transactions.',
        tags: ['Recurring Transactions'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'account_id', 'type', 'amount', 'currency_code', 'frequency', 'next_run_at'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Netflix subscription'),
                    new OA\Property(property: 'account_id', type: 'integer', example: 1),
                    new OA\Property(property: 'category_id', type: 'integer', nullable: true, example: 3),
                    new OA\Property(property: 'type', type: 'string', enum: ['income', 'expense'], example: 'expense'),
                    new OA\Property(property: 'amount', type: 'number', format: 'float', example: 15.99),
                    new OA\Property(property: 'currency_code', type: 'string', example: 'USD'),
                    new OA\Property(property: 'frequency', type: 'string', enum: ['daily', 'weekly', 'monthly', 'yearly'], example: 'monthly'),
                    new OA\Property(property: 'next_run_at', type: 'string', format: 'date', example: '2026-08-01'),
                    new OA\Property(property: 'comment', type: 'string', nullable: true, example: 'Streaming service'),
                    new OA\Property(property: 'is_active', type: 'boolean', example: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Recurring transaction created'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(StoreRecurringTransactionRequest $request): JsonResponse
    {
        $this->authorize('create', RecurringTransaction::class);
        $recurring = $this->service->create(auth()->user(), $request->validated());

        return (new RecurringTransactionResource($recurring))->response()->setStatusCode(201);
    }

    #[OA\Get(
        path: '/api/v1/recurring-transactions/{id}',
        summary: 'Get a specific recurring transaction',
        tags: ['Recurring Transactions'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Recurring transaction details'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function show(RecurringTransaction $recurringTransaction): RecurringTransactionResource
    {
        $this->authorize('view', $recurringTransaction);

        return new RecurringTransactionResource($this->service->show($recurringTransaction));
    }

    #[OA\Patch(
        path: '/api/v1/recurring-transactions/{id}',
        summary: 'Update a recurring transaction',
        tags: ['Recurring Transactions'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(properties: [
                new OA\Property(property: 'name', type: 'string', example: 'Updated Subscription'),
                new OA\Property(property: 'amount', type: 'number', format: 'float', example: 19.99),
                new OA\Property(property: 'frequency', type: 'string', enum: ['daily', 'weekly', 'monthly', 'yearly'], example: 'yearly'),
                new OA\Property(property: 'next_run_at', type: 'string', format: 'date', example: '2027-01-01'),
                new OA\Property(property: 'is_active', type: 'boolean', example: false),
            ])
        ),
        responses: [
            new OA\Response(response: 200, description: 'Recurring transaction updated'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(UpdateRecurringTransactionRequest $request, RecurringTransaction $recurringTransaction): RecurringTransactionResource
    {
        $this->authorize('update', $recurringTransaction);
        $recurring = $this->service->update($recurringTransaction, $request->validated());

        return new RecurringTransactionResource($recurring);
    }

    #[OA\Delete(
        path: '/api/v1/recurring-transactions/{id}',
        summary: 'Delete a recurring transaction',
        tags: ['Recurring Transactions'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Recurring transaction deleted'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ]
    )]
    public function destroy(RecurringTransaction $recurringTransaction): Response
    {
        $this->authorize('delete', $recurringTransaction);
        $this->service->delete($recurringTransaction);

        return response()->noContent();
    }
}

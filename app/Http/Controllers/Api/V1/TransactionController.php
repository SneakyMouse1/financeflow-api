<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTransactionRequest;
use App\Http\Requests\UpdateTransactionRequest;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use App\Services\TransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use OpenApi\Attributes as OA;

class TransactionController extends Controller
{
    public function __construct(private readonly TransactionService $service) {}

    #[OA\Get(
        path: '/api/v1/transactions',
        summary: 'Get a paginated list of transactions with optional filters',
        tags: ['Transactions'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'account_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 1)),
            new OA\Parameter(name: 'category_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 2)),
            new OA\Parameter(name: 'type', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['income', 'expense', 'transfer'], example: 'expense')),
            new OA\Parameter(name: 'date_from', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date', example: '2026-01-01')),
            new OA\Parameter(name: 'date_to', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date', example: '2026-12-31')),
            new OA\Parameter(name: 'search', in: 'query', required: false, schema: new OA\Schema(type: 'string', example: 'grocery')),
            new OA\Parameter(name: 'tag', in: 'query', required: false, schema: new OA\Schema(type: 'string', example: 'food')),
            new OA\Parameter(name: 'currency_code', in: 'query', required: false, schema: new OA\Schema(type: 'string', example: 'USD')),
            new OA\Parameter(name: 'sort', in: 'query', required: false, description: 'Sort by field. Prefix with - for descending (e.g. -amount)', schema: new OA\Schema(type: 'string', example: '-amount')),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 20)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated list of transactions'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Transaction::class);
        $transactions = $this->service->getAll(auth()->user(), $request->query());
        return TransactionResource::collection($transactions);
    }

    #[OA\Post(
        path: '/api/v1/transactions',
        summary: 'Create a new transaction (income, expense, or transfer)',
        tags: ['Transactions'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['account_id', 'type', 'amount', 'currency_code', 'date'],
                properties: [
                    new OA\Property(property: 'account_id', type: 'integer', example: 1),
                    new OA\Property(property: 'category_id', type: 'integer', nullable: true, example: 2),
                    new OA\Property(property: 'type', type: 'string', enum: ['income', 'expense', 'transfer'], example: 'expense'),
                    new OA\Property(property: 'amount', type: 'number', format: 'float', example: 49.99),
                    new OA\Property(property: 'currency_code', type: 'string', example: 'USD'),
                    new OA\Property(property: 'date', type: 'string', format: 'date', example: '2026-07-09'),
                    new OA\Property(property: 'comment', type: 'string', nullable: true, example: 'Weekly groceries'),
                    new OA\Property(property: 'tags', type: 'array', items: new OA\Items(type: 'integer'), example: [1, 3]),
                    new OA\Property(property: 'to_account_id', type: 'integer', nullable: true, description: 'Required if type=transfer', example: null),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Transaction created'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(StoreTransactionRequest $request): JsonResponse
    {
        $this->authorize('create', Transaction::class);
        $transaction = $this->service->create(auth()->user(), $request->validated());
        return (new TransactionResource($transaction))->response()->setStatusCode(201);
    }

    #[OA\Get(
        path: '/api/v1/transactions/{id}',
        summary: 'Get a specific transaction with relations (account, category, tags, attachments)',
        tags: ['Transactions'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Transaction details'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function show(Transaction $transaction): TransactionResource
    {
        $this->authorize('view', $transaction);
        $transaction->load(['account', 'category', 'tags', 'attachments']);
        return new TransactionResource($transaction);
    }

    #[OA\Patch(
        path: '/api/v1/transactions/{id}',
        summary: 'Update a transaction (transfer transactions are locked)',
        tags: ['Transactions'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(properties: [
                new OA\Property(property: 'category_id', type: 'integer', nullable: true, example: 3),
                new OA\Property(property: 'amount', type: 'number', format: 'float', example: 99.00),
                new OA\Property(property: 'date', type: 'string', format: 'date', example: '2026-07-10'),
                new OA\Property(property: 'comment', type: 'string', nullable: true, example: 'Updated comment'),
                new OA\Property(property: 'tags', type: 'array', items: new OA\Items(type: 'integer'), example: [2]),
            ])
        ),
        responses: [
            new OA\Response(response: 200, description: 'Transaction updated'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(UpdateTransactionRequest $request, Transaction $transaction): TransactionResource
    {
        $this->authorize('update', $transaction);
        $transaction = $this->service->update($transaction, $request->validated());
        return new TransactionResource($transaction);
    }

    #[OA\Delete(
        path: '/api/v1/transactions/{id}',
        summary: 'Soft-delete a transaction',
        tags: ['Transactions'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Transaction deleted'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ]
    )]
    public function destroy(Transaction $transaction): Response
    {
        $this->authorize('delete', $transaction);
        $this->service->delete($transaction);
        return response()->noContent();
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAccountRequest;
use App\Http\Requests\UpdateAccountRequest;
use App\Http\Resources\AccountResource;
use App\Models\Account;
use App\Services\AccountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use OpenApi\Attributes as OA;

class AccountController extends Controller
{

    public function __construct(private readonly AccountService $service) {}
    #[OA\Get(
        path: '/api/v1/accounts',
        summary: 'Get a list of all user accounts',
        tags: ['Accounts'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'archived', in: 'query', required: false,
                description: 'Set to true to include archived accounts',
                schema: new OA\Schema(type: 'boolean', example: false)
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: 'List of accounts'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Account::class);
        $accounts = $this->service->getAll(auth()->user());
        return AccountResource::collection($accounts);
    }

    #[OA\Post(
        path: '/api/v1/accounts',
        summary: 'Create a new account',
        tags: ['Accounts'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'type', 'currency_code'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Main Card'),
                    new OA\Property(property: 'type', type: 'string', enum: ['card', 'cash', 'crypto', 'deposit', 'investment'], example: 'card'),
                    new OA\Property(property: 'currency_code', type: 'string', example: 'USD'),
                    new OA\Property(property: 'balance', type: 'number', format: 'float', example: 1500.00),
                    new OA\Property(property: 'color', type: 'string', nullable: true, example: '#3B82F6'),
                    new OA\Property(property: 'icon', type: 'string', nullable: true, example: 'credit-card'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Account created'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(StoreAccountRequest $request): JsonResponse
    {
        $this->authorize('create', Account::class);
        $account = $this->service->create(auth()->user(), $request->validated());
        return (new AccountResource($account))->response()->setStatusCode(201);
    }

    #[OA\Get(
        path: '/api/v1/accounts/{id}',
        summary: 'Get a specific account by ID',
        tags: ['Accounts'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Account details'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function show(Account $account): AccountResource
    {
        $this->authorize('view', $account);
        return new AccountResource($account);
    }

    #[OA\Patch(
        path: '/api/v1/accounts/{id}',
        summary: 'Update a specific account',
        tags: ['Accounts'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(properties: [
                new OA\Property(property: 'name', type: 'string', example: 'Updated Name'),
                new OA\Property(property: 'type', type: 'string', enum: ['card', 'cash', 'crypto', 'deposit', 'investment'], example: 'card'),
                new OA\Property(property: 'currency_code', type: 'string', example: 'EUR'),
                new OA\Property(property: 'color', type: 'string', nullable: true, example: '#10B981'),
                new OA\Property(property: 'icon', type: 'string', nullable: true, example: 'wallet'),
                new OA\Property(property: 'is_archived', type: 'boolean', example: false),
            ])
        ),
        responses: [
            new OA\Response(response: 200, description: 'Account updated'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(UpdateAccountRequest $request, Account $account): AccountResource
    {
        $this->authorize('update', $account);
        $account = $this->service->update($account, $request->validated());
        return new AccountResource($account);
    }
    #[OA\Delete(
        path: '/api/v1/accounts/{id}',
        summary: 'Soft-delete an account (cascades to transactions)',
        tags: ['Accounts'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Account deleted'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ]
    )]
    public function destroy(Account $account): Response
    {
        $this->authorize('delete', $account);
        $this->service->delete($account);
        return response()->noContent();
    }
}

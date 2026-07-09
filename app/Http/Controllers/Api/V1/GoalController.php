<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGoalDepositRequest;
use App\Http\Requests\StoreGoalRequest;
use App\Http\Requests\UpdateGoalRequest;
use App\Http\Resources\GoalResource;
use App\Models\Goal;
use App\Services\GoalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use OpenApi\Attributes as OA;

class GoalController extends Controller
{
    public function __construct(private readonly GoalService $service) {}

    #[OA\Get(
        path: '/api/v1/goals',
        summary: 'Get all savings goals for the authenticated user',
        tags: ['Goals'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'List of goals with progress'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Goal::class);
        $goals = $this->service->getAll(auth()->user());

        return GoalResource::collection($goals);
    }

    #[OA\Post(
        path: '/api/v1/goals',
        summary: 'Create a new savings goal',
        tags: ['Goals'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'target_amount', 'currency_code'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'New Laptop'),
                    new OA\Property(property: 'target_amount', type: 'number', format: 'float', example: 1500.00),
                    new OA\Property(property: 'currency_code', type: 'string', example: 'USD'),
                    new OA\Property(property: 'deadline', type: 'string', format: 'date', nullable: true, example: '2027-01-01'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Goal created'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(StoreGoalRequest $request): JsonResponse
    {
        $this->authorize('create', Goal::class);
        $goal = $this->service->create(auth()->user(), $request->validated());

        return (new GoalResource($goal))->response()->setStatusCode(201);
    }

    #[OA\Get(
        path: '/api/v1/goals/{id}',
        summary: 'Get a specific goal with all deposits',
        tags: ['Goals'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Goal details with deposit history and progress'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function show(Goal $goal): GoalResource
    {
        $this->authorize('view', $goal);
        $goal->load('deposits');

        return new GoalResource($goal);
    }

    #[OA\Patch(
        path: '/api/v1/goals/{id}',
        summary: 'Update a goal name, deadline, or status',
        tags: ['Goals'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(properties: [
                new OA\Property(property: 'name', type: 'string', example: 'Updated Goal Name'),
                new OA\Property(property: 'deadline', type: 'string', format: 'date', nullable: true, example: '2027-06-01'),
                new OA\Property(property: 'status', type: 'string', enum: ['active', 'completed', 'paused'], example: 'paused'),
            ])
        ),
        responses: [
            new OA\Response(response: 200, description: 'Goal updated'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(UpdateGoalRequest $request, Goal $goal): GoalResource
    {
        $this->authorize('update', $goal);
        $goal = $this->service->update($goal, $request->validated());

        return new GoalResource($goal);
    }

    #[OA\Post(
        path: '/api/v1/goals/{id}/deposit',
        summary: 'Add a deposit to a savings goal',
        description: 'When current_amount reaches target_amount the goal status is automatically set to completed.',
        tags: ['Goals'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['amount'],
                properties: [
                    new OA\Property(property: 'amount', type: 'number', format: 'float', example: 200.00),
                    new OA\Property(property: 'comment', type: 'string', nullable: true, example: 'Monthly savings'),
                    new OA\Property(property: 'account_id', type: 'integer', nullable: true, example: 1),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Goal updated with new deposit applied'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function deposit(StoreGoalDepositRequest $request, Goal $goal): GoalResource
    {
        $this->authorize('update', $goal);
        $goal = $this->service->deposit($goal, $request->validated());

        return new GoalResource($goal);
    }

    #[OA\Delete(
        path: '/api/v1/goals/{id}',
        summary: 'Delete a savings goal',
        tags: ['Goals'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Goal deleted'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ]
    )]
    public function destroy(Goal $goal): Response
    {
        $this->authorize('delete', $goal);
        $this->service->delete($goal);

        return response()->noContent();
    }
}

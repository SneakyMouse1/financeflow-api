<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $service) {}

    #[OA\Get(
        path: '/api/v1/dashboard',
        summary: 'Get dashboard summary for the authenticated user',
        description: 'Returns total balance, monthly income/expense, chart data, recent transactions, and top spending categories.',
        tags: ['Dashboard'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Dashboard summary data',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', properties: [
                        new OA\Property(property: 'total_balance', type: 'number', format: 'float', example: 5420.00),
                        new OA\Property(property: 'monthly_income', type: 'number', format: 'float', example: 3000.00),
                        new OA\Property(property: 'monthly_expense', type: 'number', format: 'float', example: 1500.00),
                        new OA\Property(property: 'recent_transactions', type: 'array', items: new OA\Items(type: 'object')),
                        new OA\Property(property: 'chart_data', type: 'array', items: new OA\Items(type: 'object')),
                        new OA\Property(property: 'top_categories', type: 'array', items: new OA\Items(type: 'object')),
                    ], type: 'object')
                ])
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $data = $this->service->getData($request->user());

        return response()->json(['data' => $data]);
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Enums\TransactionType;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class ReportController extends Controller
{
    #[OA\Get(
        path: '/api/v1/reports',
        summary: 'Get aggregated transaction report with optional filters',
        description: 'Returns income/expense summary totals and a breakdown by category. All filters are optional.',
        tags: ['Reports'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'date_from', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date', example: '2026-01-01')),
            new OA\Parameter(name: 'date_to', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date', example: '2026-12-31')),
            new OA\Parameter(name: 'account_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 1)),
            new OA\Parameter(name: 'category_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 2)),
            new OA\Parameter(name: 'type', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['income', 'expense', 'transfer'], example: 'expense')),
            new OA\Parameter(name: 'currency_code', in: 'query', required: false, schema: new OA\Schema(type: 'string', example: 'USD')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Report data with summary and category breakdown',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', properties: [
                        new OA\Property(property: 'summary', properties: [
                            new OA\Property(property: 'income', type: 'number', example: 3000.00),
                            new OA\Property(property: 'expense', type: 'number', example: 1200.00),
                            new OA\Property(property: 'net', type: 'number', example: 1800.00),
                        ], type: 'object'),
                        new OA\Property(property: 'by_category', type: 'array', items: new OA\Items(type: 'object')),
                        new OA\Property(property: 'filters', type: 'object'),
                    ], type: 'object')
                ])
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user instanceof User) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $query = $user->transactions()
            ->with(['category', 'account'])
            ->when($request->date_from, fn ($q, $v) => $q->whereDate('date', '>=', $v))
            ->when($request->date_to, fn ($q, $v) => $q->whereDate('date', '<=', $v))
            ->when($request->account_id, fn ($q, $v) => $q->where('account_id', $v))
            ->when($request->category_id, fn ($q, $v) => $q->where('category_id', $v))
            ->when($request->type, fn ($q, $v) => $q->where('type', $v))
            ->when($request->currency_code, fn ($q, $v) => $q->where('currency_code', $v));

        // Summary totals
        $totals = (clone $query)
            ->whereIn('type', ['income', 'expense'])
            ->select('type', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('type')
            ->get();

        $income  = 0.0;
        $expense = 0.0;

        foreach ($totals as $row) {
            $typeVal = $row->getAttribute('type');
            $type = $typeVal instanceof TransactionType ? $typeVal->value : (string) $typeVal;
            if ($type === 'income') {
                $income = (float) $row->getAttribute('total');
            } elseif ($type === 'expense') {
                $expense = (float) $row->getAttribute('total');
            }
        }

        // Breakdown by category
        $byCategory = (clone $query)
            ->where('type', 'expense')
            ->whereNotNull('category_id')
            ->select('category_id', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('category_id')
            ->with('category')
            ->orderByDesc('total')
            ->get()
            ->map(function (\Illuminate\Database\Eloquent\Model $t) {
                $categoryName = null;
                if ($t instanceof Transaction) {
                    $categoryName = $t->category?->name;
                }
                return [
                    'category' => $categoryName,
                    'total'    => (float) $t->getAttribute('total'),
                    'count'    => (int) $t->getAttribute('count'),
                ];
            });

        return response()->json([
            'data' => [
                'summary' => [
                    'income'  => $income,
                    'expense' => $expense,
                    'net'     => round($income - $expense, 2),
                ],
                'by_category' => $byCategory,
                'filters'     => $request->only(['date_from', 'date_to', 'account_id', 'category_id', 'type', 'currency_code']),
            ],
        ]);
    }

    #[OA\Get(
        path: '/api/v1/reports/export',
        summary: 'Export report — coming in Stage 10 (PDF / Excel / CSV via queue)',
        tags: ['Reports'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 501,
                description: 'Not yet implemented',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Export feature is planned for Stage 10. Will support PDF, Excel, and CSV formats.')
                    ], type: 'object')
                ])
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function export(Request $request): JsonResponse
    {
        return response()->json([
            'data' => [
                'message' => 'Export feature is planned for Stage 10. Will support PDF, Excel, and CSV formats.',
            ],
        ], 501);
    }
}

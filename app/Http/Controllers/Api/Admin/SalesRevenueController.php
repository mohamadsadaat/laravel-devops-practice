<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\SalesService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SalesRevenueController extends Controller
{
    public function __construct(
        private readonly SalesService $salesService
    ) {
    }

    public function index(): JsonResponse
    {
        $revenue = $this->salesService->getRevenueSummary();

        return response()->json([
            'data' => [
                'last_week' => [
                    'period' => 'Last Week',
                    'revenue' => (float) $revenue['last_week'],
                    'formatted' => number_format($revenue['last_week'], 2) . ' SAR'
                ],
                'last_month' => [
                    'period' => 'Last Month', 
                    'revenue' => (float) $revenue['last_month'],
                    'formatted' => number_format($revenue['last_month'], 2) . ' SAR'
                ],
                'total' => [
                    'period' => 'Total All Time',
                    'revenue' => (float) $revenue['total'],
                    'formatted' => number_format($revenue['total'], 2) . ' SAR'
                ],
            ],
            'summary' => [
                'total_revenue' => (float) $revenue['total'],
                'last_month_revenue' => (float) $revenue['last_month'],
                'last_week_revenue' => (float) $revenue['last_week'],
                'last_month_contribution' => $revenue['total'] > 0 ? round(($revenue['last_month'] / $revenue['total']) * 100, 2) : 0,
                'last_week_contribution' => $revenue['total'] > 0 ? round(($revenue['last_week'] / $revenue['total']) * 100, 2) : 0,
            ]
        ]);
    }

    public function byDateRange(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date'
        ]);

        $revenue = $this->salesService->getRevenueByDateRange(
            $request->start_date,
            $request->end_date
        );

        return response()->json([
            'data' => [
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'revenue' => (float) $revenue,
                'formatted' => number_format($revenue, 2) . ' SAR'
            ]
        ]);
    }

    public function monthly(Request $request): JsonResponse
    {
        $request->validate([
            'year' => 'required|integer|min:2020|max:' . date('Y')
        ]);

        $monthlyRevenue = $this->salesService->getMonthlyRevenue($request->year);

        $data = [];
        for ($month = 1; $month <= 12; $month++) {
            $revenue = $monthlyRevenue[$month] ?? 0;
            $data[] = [
                'month' => $month,
                'month_name' => date('F', mktime(0, 0, 0, $month, 1)),
                'revenue' => (float) $revenue,
                'formatted' => number_format($revenue, 2) . ' SAR'
            ];
        }

        return response()->json([
            'data' => $data,
            'year' => $request->year,
            'total_yearly_revenue' => (float) array_sum($monthlyRevenue),
            'formatted_total' => number_format(array_sum($monthlyRevenue), 2) . ' SAR'
        ]);
    }
}

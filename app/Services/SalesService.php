<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\DB;

class SalesService
{
    public function getRevenueSummary(): array
    {
        return [
            'last_week' => $this->getRevenueForPeriod('last_week'),
            'last_month' => $this->getRevenueForPeriod('last_month'),
            'total' => $this->getTotalRevenue(),
        ];
    }

    private function getRevenueForPeriod(string $period): float
    {
        $query = Order::query()
            ->where('status', 'delivered');

        switch ($period) {
            case 'last_week':
                $query->whereBetween('created_at', [
                    now()->subWeek()->startOfWeek(),
                    now()->subWeek()->endOfWeek()
                ]);
                break;
            case 'last_month':
                $query->whereBetween('created_at', [
                    now()->subMonth()->startOfMonth(),
                    now()->subMonth()->endOfMonth()
                ]);
                break;
        }

        return $query->sum('total') ?? 0;
    }

    private function getTotalRevenue(): float
    {
        return Order::where('status', 'delivered')->sum('total') ?? 0;
    }

    public function getRevenueByDateRange(string $startDate, string $endDate): float
    {
        return Order::where('status', 'delivered')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('total') ?? 0;
    }

    public function getMonthlyRevenue(int $year): array
    {
        return Order::where('status', 'delivered')
            ->whereYear('created_at', $year)
            ->selectRaw('MONTH(created_at) as month, SUM(total) as revenue')
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('revenue', 'month')
            ->toArray();
    }
}

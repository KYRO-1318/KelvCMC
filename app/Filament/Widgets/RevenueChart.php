<?php

namespace App\Filament\Widgets;

use App\Models\Payment;
use Filament\Widgets\ChartWidget;

class RevenueChart extends ChartWidget
{
    protected static ?string $heading = 'Payments received';

    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $months = collect(range(5, 0))->map(function ($offset) {
            $month = now()->startOfMonth()->subMonths($offset);

            return [
                'label' => $month->format('M Y'),
                'total' => (float) Payment::where('status', 'paid')
                    ->whereBetween('paid_at', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
                    ->sum('amount'),
            ];
        });

        return [
            'datasets' => [
                [
                    'label' => 'Payments',
                    'data' => $months->pluck('total')->all(),
                    'backgroundColor' => 'rgba(139, 92, 246, 0.15)',
                    'borderColor' => 'rgb(139, 92, 246)',
                    'fill' => true,
                ],
            ],
            'labels' => $months->pluck('label')->all(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}

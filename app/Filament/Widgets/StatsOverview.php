<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use App\Models\Service;
use App\Models\Ticket;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $revenue = Invoice::where('status', 'paid')->whereBetween('paid_at', [now()->startOfMonth(), now()->endOfMonth()])->sum('total');
        $pendingRevenue = Invoice::whereIn('status', ['open', 'overdue'])->sum('total');

        return [
            Stat::make('Monthly revenue', kelvcmc_money($revenue))
                ->description('Paid invoices this month')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('Pending revenue', kelvcmc_money($pendingRevenue))
                ->description('Open + overdue invoices')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Active services', Service::where('status', 'active')->count())
                ->description(Service::where('status', 'pending')->count().' pending provisioning')
                ->descriptionIcon('heroicon-m-server')
                ->color('info'),

            Stat::make('Customers', User::whereHas('roles', fn ($q) => $q->where('name', 'client'))->count())
                ->description('Registered accounts')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),

            Stat::make('Open tickets', Ticket::where('status', '!=', 'closed')->count())
                ->description(Ticket::where('priority', 'urgent')->where('status', '!=', 'closed')->count().' urgent')
                ->descriptionIcon('heroicon-m-lifebuoy')
                ->color('danger'),
        ];
    }
}

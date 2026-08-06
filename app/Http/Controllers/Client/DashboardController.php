<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // Chart data: payments per month, last 6 months.
        $months = collect(range(5, 0))->map(function ($offset) use ($user) {
            $month = now()->startOfMonth()->subMonths($offset);

            return [
                'label' => $month->format('M'),
                'total' => (float) $user->payments()->where('status', 'paid')
                    ->whereBetween('paid_at', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
                    ->sum('amount'),
            ];
        });

        return view('client.dashboard', [
            'user' => $user,
            'activeServices' => $user->services()->whereIn('status', ['active', 'pending'])->count(),
            'openTickets' => $user->openTickets()->count(),
            'openInvoices' => $user->openInvoices()->count(),
            'openInvoicesTotal' => $user->openInvoices()->sum('total'),
            'upcomingRenewals' => $user->services()
                ->where('status', 'active')
                ->whereNotNull('expires_at')
                ->orderBy('expires_at')
                ->limit(4)
                ->get(),
            'recentInvoices' => $user->invoices()->latest()->limit(5)->get(),
            'recentOrders' => $user->orders()->latest()->limit(5)->get(),
            'chartMonths' => $months->pluck('label')->values(),
            'chartTotals' => $months->pluck('total')->values(),
        ]);
    }
}

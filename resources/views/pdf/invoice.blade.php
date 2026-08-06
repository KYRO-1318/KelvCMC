<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->number }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; color: #1e293b; font-size: 12px; line-height: 1.5; margin: 0; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; padding-bottom: 20px; border-bottom: 3px solid #7c3aed; }
        .brand { display: flex; align-items: center; gap: 10px; }
        .brand .logo { width: 36px; height: 36px; background: #7c3aed; color: #fff; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; font-weight: bold; }
        .title { text-align: right; }
        .title h1 { margin: 0; font-size: 26px; color: #7c3aed; }
        .title .meta { color: #64748b; font-size: 11px; margin-top: 4px; }
        .parties { display: flex; justify-content: space-between; margin-top: 24px; }
        .party h3 { margin: 0 0 6px; font-size: 11px; text-transform: uppercase; letter-spacing: 0.08em; color: #94a3b8; }
        .party .name { font-weight: bold; font-size: 14px; }
        .party p { margin: 2px 0; color: #475569; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 28px; }
        table.items th { background: #f1f5f9; text-align: left; font-size: 10px; text-transform: uppercase; letter-spacing: 0.06em; color: #64748b; padding: 10px; }
        table.items th.right, table.items td.right { text-align: right; }
        table.items td { padding: 10px; border-bottom: 1px solid #e2e8f0; }
        .totals { margin-left: auto; width: 260px; margin-top: 20px; }
        .totals .row { display: flex; justify-content: space-between; padding: 5px 0; color: #475569; }
        .totals .row.total { font-size: 16px; font-weight: bold; color: #0f172a; border-top: 2px solid #7c3aed; margin-top: 6px; padding-top: 10px; }
        .footer { margin-top: 40px; padding-top: 12px; border-top: 1px solid #e2e8f0; color: #94a3b8; font-size: 10px; text-align: center; }
        .status-badge { display: inline-block; margin-top: 6px; background: #7c3aed; color: #fff; padding: 4px 10px; border-radius: 999px; font-size: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="brand">
            <div class="logo">K</div>
            <div>
                <div style="font-weight: bold; font-size: 16px;">{{ kelvcmc_brand() }}</div>
                <div style="color: #64748b; font-size: 11px;">{{ config('kelvcmc.brand.tagline') }}</div>
            </div>
        </div>
        <div class="title">
            <h1>INVOICE</h1>
            <div class="meta">
                {{ $invoice->number }}<br>
                Issued: {{ $invoice->created_at->format('d M Y') }}<br>
                Due: {{ $invoice->due_at?->format('d M Y') ?? '—' }}
            </div>
            <span class="status-badge">{{ strtoupper($invoice->status) }}</span>
        </div>
    </div>

    <div class="parties">
        <div class="party">
            <h3>From</h3>
            <div class="name">{{ kelvcmc_brand() }}</div>
            <p>{{ config('kelvcmc.brand.tagline') }}</p>
        </div>
        <div class="party" style="text-align: right;">
            <h3>Billed to</h3>
            <div class="name">{{ $invoice->user->name }}</div>
            @if ($invoice->user->company)<p>{{ $invoice->user->company }}</p>@endif
            <p>{{ $invoice->user->email }}</p>
            @if ($invoice->user->address)<p>{{ $invoice->user->address }}</p>@endif
            @if ($invoice->user->city)<p>{{ $invoice->user->city }} {{ $invoice->user->zip }} {{ $invoice->user->country }}</p>@endif
        </div>
    </div>

    <table class="items">
        <thead>
            <tr>
                <th>Description</th>
                <th class="right">Qty</th>
                <th class="right">Unit price</th>
                <th class="right">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice->items as $item)
                <tr>
                    <td>{{ $item->description }}</td>
                    <td class="right">{{ rtrim(rtrim(number_format((float) $item->quantity, 2), '0'), '.') }}</td>
                    <td class="right">{{ number_format((float) $item->unit_price, 2) }} {{ $invoice->currency }}</td>
                    <td class="right">{{ number_format((float) $item->total, 2) }} {{ $invoice->currency }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <div class="row"><span>Subtotal</span><span>{{ number_format((float) $invoice->subtotal, 2) }} {{ $invoice->currency }}</span></div>
        @if ($invoice->discount > 0)
            <div class="row"><span>Discount</span><span>− {{ number_format((float) $invoice->discount, 2) }} {{ $invoice->currency }}</span></div>
        @endif
        <div class="row"><span>VAT ({{ number_format((float) $invoice->tax_rate, 2) }}%)</span><span>{{ number_format((float) $invoice->tax_amount, 2) }} {{ $invoice->currency }}</span></div>
        <div class="row total"><span>Total due</span><span>{{ number_format((float) $invoice->total, 2) }} {{ $invoice->currency }}</span></div>
    </div>

    <div class="footer">
        {{ kelvcmc_brand() }} · Thank you for your business! · {{ route('billing.index') }}
    </div>
</body>
</html>

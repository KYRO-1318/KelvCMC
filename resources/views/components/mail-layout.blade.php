@props([])

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif; background: #0f172a; color: #e2e8f0; margin: 0; padding: 32px 16px; }
        .wrap { max-width: 560px; margin: 0 auto; background: #1e293b; border-radius: 12px; padding: 32px; }
        .logo { color: #a78bfa; font-weight: 800; font-size: 20px; }
        h1 { margin: 20px 0 8px; font-size: 20px; color: #fff; }
        p { line-height: 1.6; color: #94a3b8; }
        .btn { display: inline-block; margin-top: 16px; background: linear-gradient(135deg, #8b5cf6, #7c3aed); color: #fff; text-decoration: none; padding: 12px 22px; border-radius: 8px; font-weight: 600; }
        .box { margin-top: 20px; background: #0f172a; border: 1px solid #334155; border-radius: 8px; padding: 16px; font-size: 13px; color: #cbd5e1; }
        .footer { margin-top: 28px; padding-top: 16px; border-top: 1px solid #334155; font-size: 12px; color: #64748b; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="logo">{{ kelvcmc_brand() }}</div>
        {{ $slot }}
        <div class="footer">
            © {{ date('Y') }} {{ kelvcmc_brand() }} — {{ config('kelvcmc.brand.tagline') }}<br>
            You are receiving this email because you have an account with us.
        </div>
    </div>
</body>
</html>

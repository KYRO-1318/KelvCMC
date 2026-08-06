@props(['title' => null])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}"><title>{{ $title ? $title.' — ' : '' }}{{ kelvcmc_brand() }}</title>@vite(['resources/css/app.css', 'resources/js/app.js'])</head>
<body class="min-h-screen bg-slate-950 font-sans text-slate-100 antialiased"><header class="border-b border-slate-800 bg-slate-900/80"><div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4"><a href="{{ url('/admin') }}" class="font-bold text-white">{{ kelvcmc_brand() }}</a><a href="{{ route('dashboard') }}" class="text-sm text-slate-400 hover:text-white">Client portal</a></div></header><main class="mx-auto max-w-7xl px-4 py-8">{{ $slot }}</main></body>
</html>

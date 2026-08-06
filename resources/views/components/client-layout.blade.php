@props(['title' => null])

<x-layouts.client :title="$title">
    {{ $slot }}
</x-layouts.client>

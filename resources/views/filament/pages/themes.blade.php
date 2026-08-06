<x-filament-panels::page>
    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
        @foreach (config('themes.themes', []) as $id => $theme)
            <button
                wire:click="activate('{{ $id }}')"
                class="group relative overflow-hidden rounded-xl border border-white/10 p-5 text-left transition hover:border-primary-500/50"
            >
                <div class="flex items-center justify-between">
                    <span class="text-sm font-semibold text-white">{{ $theme['name'] }}</span>
                    @if ($this->active() === $id)
                        <span class="rounded-full bg-primary-500/20 px-2 py-0.5 text-xs text-primary-300">Active</span>
                    @endif
                </div>
                <p class="mt-2 text-xs text-gray-400">{{ $theme['description'] }}</p>
                <div class="mt-4 h-2 w-full overflow-hidden rounded-full bg-gray-800">
                    <div class="h-full w-1/3 rounded-full {{ $id === 'midnight' ? 'bg-cyan-400' : ($id === 'aurora' ? 'bg-teal-400' : 'bg-violet-400') }}"></div>
                </div>
            </button>
        @endforeach
    </div>

    <div class="mt-6 rounded-xl border border-white/10 bg-white/5 p-4 text-xs text-gray-400">
        Themes are CSS files in <code class="text-violet-300">resources/css/themes/</code> that override the client portal
        CSS variables. See <code class="text-violet-300">docs/themes.md</code> to create your own.
    </div>
</x-filament-panels::page>

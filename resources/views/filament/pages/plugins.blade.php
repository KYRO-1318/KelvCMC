<x-filament-panels::page>
    <div class="space-y-6">
        <div>
            <h2 class="text-base font-semibold text-white">Modules</h2>
            <p class="text-xs text-gray-400">Discovered from <code>app/Modules</code></p>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            @forelse ($this->modules() as $id => $module)
                <div class="rounded-xl border border-white/10 bg-white/5 p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h3 class="text-sm font-semibold text-white">{{ $module->name() }}</h3>
                            <p class="mt-1 text-xs text-gray-400">{{ $module->description() }}</p>
                        </div>
                        <x-filament::button
                            size="sm"
                            color="{{ $this->isEnabled($id) ? 'danger' : 'success' }}"
                            wire:click="toggle('module', '{{ $id }}')"
                        >
                            {{ $this->isEnabled($id) ? 'Disable' : 'Enable' }}
                        </x-filament::button>
                    </div>
                </div>
            @empty
                <p class="text-sm text-gray-400">No modules discovered.</p>
            @endforelse
        </div>

        <div class="pt-4">
            <h2 class="text-base font-semibold text-white">Plugins</h2>
            <p class="text-xs text-gray-400">Discovered from {{ implode(', ', config('modules.paths', [])) }}</p>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            @forelse ($this->plugins() as $id => $plugin)
                <div class="rounded-xl border border-white/10 bg-white/5 p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h3 class="text-sm font-semibold text-white">{{ $id }} <span class="text-xs font-normal text-gray-500">v{{ $plugin['version'] ?? '1.0.0' }}</span></h3>
                            <p class="mt-1 text-xs text-gray-400">{{ $plugin['description'] ?? '' }}</p>
                            <p class="mt-2 text-[11px] text-gray-600">{{ str_replace(base_path(), '', $plugin['path'] ?? '') }}</p>
                        </div>
                        <x-filament::button
                            size="sm"
                            color="{{ $this->isEnabled($id) ? 'danger' : 'success' }}"
                            wire:click="toggle('plugin', '{{ $id }}')"
                        >
                            {{ $this->isEnabled($id) ? 'Disable' : 'Enable' }}
                        </x-filament::button>
                    </div>
                </div>
            @empty
                <p class="text-sm text-gray-400">No plugins discovered.</p>
            @endforelse
        </div>
    </div>
</x-filament-panels::page>

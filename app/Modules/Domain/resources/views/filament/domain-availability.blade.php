<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Domain availability
        </x-slot>

        <div class="flex gap-3">
            <x-filament::input.wrapper class="flex-1">
                <x-filament::input type="text" wire:model="domain" placeholder="example.com" />
            </x-filament::input.wrapper>

            <x-filament::button wire:click="check">
                Check
            </x-filament::button>
        </div>

        @if ($available !== null)
            <div class="mt-3 text-sm">
                @if ($available)
                    <span class="font-medium text-success-400">{{ $domain }} is available</span>
                @else
                    <span class="font-medium text-danger-400">{{ $domain }} is registered</span>
                @endif
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>

<x-filament-widgets::widget>
    <x-filament::section>
        <form wire:submit.prevent="create">
            {{ $this->form }}

            <div class="flex justify-between items-center !mt-4">
                <div class="text-xs text-gray-500 dark:text-gray-400">
                    <span class="inline-flex items-center">
                        <x-filament::icon
                            icon="heroicon-m-information-circle"
                            class="h-4 w-4 mr-1"
                        />
                        Create a shortened URL quickly
                    </span>
                </div>

                <x-filament::button
                    type="submit"
                    icon="heroicon-m-link"
                    class="mt-3"
                >
                    Create Link
                </x-filament::button>
            </div>
        </form>
    </x-filament::section>
</x-filament-widgets::widget>

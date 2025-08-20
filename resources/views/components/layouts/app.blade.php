<x-filament-panels::layout.base xmlns:x-filament-panels="http://www.w3.org/1999/html">
    <x-filament-topbar :title="$title">
        <div
            class="flex"
            x-data="{
                        theme: localStorage.getItem('theme') || 'system',
                        init: function () {
                            $dispatch('theme-changed', this.theme)
                        },
                        toggleTheme: function(newTheme) {
                            this.theme = newTheme
                            localStorage.setItem('theme', newTheme)
                            $dispatch('theme-changed', newTheme)
                        }
                    }">
            <x-filament::button
                class="!mx-1"
                icon="heroicon-m-computer-desktop"
                x-on:click="toggleTheme('system')"
                x-bind:class="theme == 'system' ? '' : 'fi-text-color-700 dark:fi-text-color-300 fi-outlined'"
            >
                System
            </x-filament::button>

            <x-filament::button
                class="!mx-1"
                icon="heroicon-m-moon"
                x-on:click="toggleTheme('dark')"
                x-bind:class="theme == 'dark' ? '' : 'fi-text-color-700 dark:fi-text-color-300 fi-outlined'"
            >
                Dark
            </x-filament::button>

            <x-filament::button
                class="!mx-1"
                icon="heroicon-m-sun"
                x-on:click="toggleTheme('light')"
                x-bind:class="theme == 'light' ? '' : 'fi-text-color-700 dark:fi-text-color-300 fi-outlined'"
            >
                Light
            </x-filament::button>
        </div>
    </x-filament-topbar>

    <div class="flex h-screen justify-center items-center">
        <x-filament::card class="w-fit">
            {{ $slot }}
        </x-filament::card>
    </div>

    <x-copyright/>
</x-filament-panels::layout.base>

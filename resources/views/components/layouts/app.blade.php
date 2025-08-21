<x-filament-panels::layout.base xmlns:x-filament-panels="http://www.w3.org/1999/html">
    <x-filament-topbar :title="$title">
        <x-filament-panels::theme-switcher/>
    </x-filament-topbar>

    <div class="flex h-screen justify-center items-center">
        <x-filament::card class="w-fit">
            {{ $slot }}
        </x-filament::card>
    </div>

    <x-copyright/>

    <script>
        // prevent closing on theme switch
        function close() {
        }
    </script>
</x-filament-panels::layout.base>

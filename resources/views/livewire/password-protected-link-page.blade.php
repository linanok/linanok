<div class="flex flex-1 items-center justify-center">
    <div class="w-full max-w-md px-4 md:mt-64">
        <form>
            {{ $this->form }}

            <x-filament::button type="submit" wire:click="submit">Submit</x-filament::button>
        </form>

        <script>
            const form = document.querySelector('form');
            form.addEventListener('submit', event => {
                event.preventDefault();
            });
        </script>
    </div>
</div>

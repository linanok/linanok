@props(['title'])

<div class="fi-topbar-ctn w-full border-b">
    <nav class="fi-topbar flex flex-wrap items-center justify-between gap-2 p-2">
        <div class="flex items-center">
            <span class="font-bold text-xl">{{ $title }}</span>
        </div>

        <div class="flex justify-end gap-2">
            {{ $slot }}
        </div>
    </nav>
</div>

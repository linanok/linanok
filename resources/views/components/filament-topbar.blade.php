@props(['title'])

<div class="fi-topbar-ctn">
    <nav class="fi-topbar flex items-center justify-between">
        <div class="flex items-center">
            <span class="font-bold text-xl">{{ $title }}</span>
        </div>
        {{ $slot }}
    </nav>
</div>

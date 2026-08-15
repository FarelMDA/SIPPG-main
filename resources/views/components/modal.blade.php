@props(['show' => false, 'title' => null, 'maxWidth' => 'lg'])
@php
    $maxWidthClass = ['sm' => 'max-w-sm', 'md' => 'max-w-md', 'lg' => 'max-w-lg', 'xl' => 'max-w-xl', '2xl' => 'max-w-2xl'][$maxWidth] ?? 'max-w-lg';
@endphp
<div
    x-data="{ show: @entangle($attributes->wire('model')) }"
    x-show="show"
    x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center p-4"
    style="display: none;"
>
    <div x-show="show" x-transition.opacity class="absolute inset-0 bg-[rgba(8,11,9,0.56)]" @click="show = false"></div>

    <div x-show="show" x-transition class="relative z-10 w-full {{ $maxWidthClass }} rounded-xl bg-white p-6 shadow-lg">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-ink-primary">{{ $title }}</h2>
            <button type="button" @click="show = false" class="text-ink-muted hover:text-ink-primary">
                <x-icon name="x" size="18" />
            </button>
        </div>

        {{ $slot }}
    </div>
</div>

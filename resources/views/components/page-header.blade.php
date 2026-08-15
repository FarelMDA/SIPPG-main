@props(['title', 'description' => null])
<div class="no-print mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-2xl font-semibold text-ink-primary">{{ $title }}</h1>
        @if($description)
            <p class="mt-1 text-sm text-ink-secondary">{{ $description }}</p>
        @endif
    </div>
    @isset($actions)
        <div class="flex items-center gap-2">{{ $actions }}</div>
    @endisset
</div>

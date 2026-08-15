@props(['title' => 'Belum ada data', 'description' => null, 'icon' => 'database'])
<div class="flex flex-col items-center justify-center gap-3 rounded-lg border border-dashed border-border py-16 text-center">
    <div class="flex h-14 w-14 items-center justify-center rounded-full bg-surface-subtle text-ink-muted">
        <x-icon :name="$icon" size="28" />
    </div>
    <div>
        <p class="font-medium text-ink-primary">{{ $title }}</p>
        @if($description)
            <p class="mt-1 text-sm text-ink-muted">{{ $description }}</p>
        @endif
    </div>
    @isset($actions)
        <div class="mt-2">{{ $actions }}</div>
    @endisset
</div>

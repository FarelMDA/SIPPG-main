@props(['variant' => 'info'])
@php
    $config = [
        'info' => ['bg-info-bg border-info-border text-info-text', 'info'],
        'warning' => ['bg-warning-bg border-warning-border text-warning-text', 'alert-triangle'],
        'danger' => ['bg-danger-bg border-danger-border text-danger-text', 'alert-circle'],
    ][$variant] ?? ['bg-info-bg border-info-border text-info-text', 'info'];
    [$styles, $iconName] = $config;
@endphp
<div {{ $attributes->merge(['class' => "flex items-start gap-2 rounded-md border px-3 py-2.5 text-sm $styles"]) }}>
    <x-icon :name="$iconName" size="16" class="mt-0.5 shrink-0" />
    <div>{{ $slot }}</div>
</div>

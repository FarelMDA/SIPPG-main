@props(['variant' => 'neutral'])
@php
    // Sama seperti button.blade.php — hindari "badge-$variant" interpolasi
    // supaya class lengkapnya terdeteksi Tailwind content scanner.
    $variantClass = match ($variant) {
        'success' => 'badge-success',
        'warning' => 'badge-warning',
        'danger' => 'badge-danger',
        'info' => 'badge-info',
        default => 'badge-neutral',
    };
@endphp
<span {{ $attributes->merge(['class' => "badge $variantClass"]) }}>{{ $slot }}</span>

@props(['variant' => 'primary', 'size' => 'md', 'type' => 'button', 'href' => null, 'icon' => null])
@php
    // Class lengkap ditulis literal (bukan interpolasi "btn-$x") agar terdeteksi
    // Tailwind content scanner — string sepert "btn-$size" tidak pernah cocok
    // sebagai teks utuh di manapun sehingga class-nya akan hilang saat build.
    $sizeClass = match ($size) {
        'sm' => 'btn-sm',
        'lg' => 'btn-lg',
        default => 'btn-md',
    };
    $variantClass = match ($variant) {
        'secondary' => 'btn-secondary',
        'outline' => 'btn-outline',
        'ghost' => 'btn-ghost',
        'danger' => 'btn-danger',
        'link' => 'btn-link',
        default => 'btn-primary',
    };
    $classes = "btn $sizeClass $variantClass";
    $tag = $href ? 'a' : 'button';
@endphp
<{{ $tag }} @if($href) href="{{ $href }}" @else type="{{ $type }}" @endif {{ $attributes->merge(['class' => $classes]) }}>
    @if($icon)
        <x-icon :name="$icon" size="16" />
    @endif
    {{ $slot }}
</{{ $tag }}>

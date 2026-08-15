@props(['padding' => 'p-6'])
<div {{ $attributes->merge(['class' => "card $padding"]) }}>
    {{ $slot }}
</div>

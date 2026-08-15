@props(['label' => null, 'name', 'type' => 'text', 'error' => null, 'description' => null, 'required' => false])
<div>
    @if($label)
        <label for="{{ $name }}" class="form-label">
            {{ $label }} @if($required)<span class="text-danger-solid">*</span>@endif
        </label>
    @endif

    <input
        type="{{ $type }}"
        id="{{ $name }}"
        name="{{ $name }}"
        @if($error) aria-invalid="true" @endif
        {{ $attributes->merge(['class' => 'form-control']) }}
    />

    @if($description)
        <p class="form-description">{{ $description }}</p>
    @endif
    @if($error)
        <p class="form-error">{{ $error }}</p>
    @endif
</div>

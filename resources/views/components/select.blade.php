@props(['label' => null, 'name', 'options' => [], 'error' => null, 'placeholder' => null, 'required' => false])
<div>
    @if($label)
        <label for="{{ $name }}" class="form-label">
            {{ $label }} @if($required)<span class="text-danger-solid">*</span>@endif
        </label>
    @endif

    <select id="{{ $name }}" name="{{ $name }}" @if($error) aria-invalid="true" @endif {{ $attributes->merge(['class' => 'form-control']) }}>
        @if($placeholder)
            <option value="">{{ $placeholder }}</option>
        @endif
        @foreach($options as $value => $label2)
            <option value="{{ $value }}">{{ $label2 }}</option>
        @endforeach
    </select>

    @if($error)
        <p class="form-error">{{ $error }}</p>
    @endif
</div>

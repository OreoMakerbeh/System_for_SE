@props(['name', 'id', 'label', 'toggleLabel' => null, 'errorBag' => 'default'])

@php($description = $toggleLabel ?? strtolower($label))

<div class="password-field">
    <label for="{{ $id }}">{{ $label }}</label>
    <div class="password-input">
        <input
            type="password"
            name="{{ $name }}"
            id="{{ $id }}"
            {{ $attributes }}
            @error($name, $errorBag) aria-invalid="true" aria-describedby="{{ $id }}-error" @enderror
        >
        <button
            class="password-toggle"
            type="button"
            data-password-toggle
            data-password-label="{{ $description }}"
            aria-controls="{{ $id }}"
            aria-label="Show {{ $description }}"
            aria-pressed="false"
            title="Show {{ $description }}"
            hidden
        >
            <svg
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.8"
                stroke-linecap="round"
                stroke-linejoin="round"
                aria-hidden="true"
                focusable="false"
            >
                <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/>
                <circle cx="12" cy="12" r="3"/>
                <path class="password-eye-slash" d="m3 3 18 18"/>
            </svg>
        </button>
    </div>
    @error($name, $errorBag)
        <small class="field-error" id="{{ $id }}-error">{{ $message }}</small>
    @enderror
</div>

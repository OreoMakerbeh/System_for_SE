@props(['name', 'label', 'selected' => ''])

@php
    $labels = [1 => 'Very poor', 2 => 'Poor', 3 => 'Okay', 4 => 'Good', 5 => 'Excellent'];
    $value = in_array((string) $selected, ['1', '2', '3', '4', '5'], true) ? (int) $selected : 0;
@endphp

<fieldset class="star-rating" data-star-rating aria-describedby="{{ $name }}-description{{ $errors->has($name) ? ' '.$name.'-error' : '' }}">
    <legend>{{ $label }}</legend>
    <div class="star-rating-options">
        @foreach ($labels as $score => $description)
            <label class="star-rating-choice" data-rating-value="{{ $score }}" data-rating-label="{{ $description }}">
                <input
                    type="radio"
                    name="{{ $name }}"
                    value="{{ $score }}"
                    required
                    @checked($value === $score)
                    aria-label="{{ $score }} {{ $score === 1 ? 'star' : 'stars' }} - {{ $description }}"
                    @error($name) aria-invalid="true" @enderror
                >
                <span class="rating-star" aria-hidden="true">★</span>
            </label>
        @endforeach
    </div>
    <p id="{{ $name }}-description" class="star-rating-description" data-rating-description role="status">{{ $value ? $value.' out of 5 · '.$labels[$value] : 'Choose one to five stars' }}</p>
    @error($name)<small class="field-error" id="{{ $name }}-error">{{ $message }}</small>@enderror
</fieldset>

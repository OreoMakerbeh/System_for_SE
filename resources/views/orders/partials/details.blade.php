<fieldset class="container-picker" aria-describedby="container-type-hint @error('container_type') container-type-error @enderror">
    <legend>Container type <span class="container-required">Required</span></legend>
    <p id="container-type-hint">Choose your container, then enter how many you need.</p>

    <div class="container-options">
        @foreach (\App\Models\Order::CONTAINER_TYPES as $type => $container)
            <div class="container-choice">
                <input
                    class="container-type-input"
                    type="radio"
                    id="container-type-{{ $type }}"
                    name="container_type"
                    value="{{ $type }}"
                    @checked(old('container_type', $containerType) === $type)
                    @error('container_type') aria-invalid="true" @enderror
                    required
                >
                <label class="container-option" for="container-type-{{ $type }}">
                    <span class="container-selection-label" aria-hidden="true">Selected</span>
                    <img src="{{ asset($container['image']) }}" alt="" width="160" height="180">
                    <span>{{ $container['label'] }}</span>
                </label>
            </div>
        @endforeach
    </div>

    @error('container_type')
        <small class="field-error" id="container-type-error">{{ $message }}</small>
    @enderror
</fieldset>

<label>
    Number of containers
    <input
        type="number"
        name="quantity"
        min="1"
        max="100"
        value="{{ old('quantity', $quantity) }}"
        required
    >
    @error('quantity')
        <small class="field-error">{{ $message }}</small>
    @enderror
</label>

<label>
    Delivery address
    <textarea
        name="delivery_address"
        rows="3"
        maxlength="1000"
        required
    >{{ old('delivery_address', $deliveryAddress) }}</textarea>
    @error('delivery_address')
        <small class="field-error">{{ $message }}</small>
    @enderror
</label>

<label>
    <span class="label-title">Delivery notes <span class="optional">OPTIONAL</span></span>
    <textarea
        name="notes"
        rows="3"
        maxlength="1000"
        placeholder="Gate code, preferred drop-off spot…"
    >{{ old('notes', $notes) }}</textarea>
    @error('notes')
        <small class="field-error">{{ $message }}</small>
    @enderror
</label>

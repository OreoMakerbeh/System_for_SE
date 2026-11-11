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

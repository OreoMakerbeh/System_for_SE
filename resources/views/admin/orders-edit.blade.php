@extends('layouts.app')

@section('title', 'Edit order #'.$order->display_number)

@section('content')
    <div class="page-heading">
        <span class="eyebrow">ORDER MANAGEMENT · #{{ $order->display_number }}</span>
        <h1>Edit order</h1>
        <p>Update the delivery details for {{ $order->user->name }}. Order status remains unchanged.</p>
    </div>

    <section class="content-section">
        <form class="form-panel form-stack admin-order-edit" method="POST" action="{{ route('admin.orders.save', $order) }}">
            @csrf
            @method('PATCH')

            <div class="form-panel-heading">
                <span class="step-index">#{{ $order->display_number }}</span>
                <div>
                    <h2>Delivery details</h2>
                    <p>Change the requested quantity, destination, or delivery note.</p>
                </div>
            </div>

            <label>
                Number of containers
                <input type="number" name="quantity" min="1" max="100" value="{{ old('quantity', $order->quantity) }}" required>
                @error('quantity')
                    <small class="field-error">{{ $message }}</small>
                @enderror
            </label>

            <label>
                Delivery address
                <textarea name="delivery_address" rows="3" maxlength="1000" required>{{ old('delivery_address', $order->delivery_address) }}</textarea>
                @error('delivery_address')
                    <small class="field-error">{{ $message }}</small>
                @enderror
            </label>

            <label>
                <span class="label-title">Delivery notes <span class="optional">OPTIONAL</span></span>
                <textarea name="notes" rows="3" maxlength="1000">{{ old('notes', $order->notes) }}</textarea>
                @error('notes')
                    <small class="field-error">{{ $message }}</small>
                @enderror
            </label>

            <label>
                Assigned rider
                <select name="rider_id">
                    <option value="">Unassigned</option>
                    @foreach ($riders as $rider)
                        <option value="{{ $rider->id }}" @selected((string) old('rider_id', $order->rider_id) === (string) $rider->id)>
                            {{ $rider->name }}
                        </option>
                    @endforeach
                </select>
                @error('rider_id')
                    <small class="field-error">Choose an active rider account.</small>
                @enderror
            </label>

            <div class="form-actions">
                <a class="text-link" href="{{ route('admin.orders') }}">Cancel</a>
                <button class="button button-primary" type="submit">Save order <span>→</span></button>
            </div>
        </form>
    </section>
@endsection

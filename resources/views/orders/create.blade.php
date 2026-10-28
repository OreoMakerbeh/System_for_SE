@extends('layouts.app')

@section('title', 'New order')

@section('content')
    <div class="page-heading">
        <span class="eyebrow">A FRESH REFILL</span>
        <h1>Place an order</h1>
        <p>Share a few details and we’ll confirm your delivery.</p>
    </div>

    <div class="order-create-layout">
        <form class="form-panel form-stack" method="POST" action="{{ route('orders.store') }}">
            @csrf

            <div class="form-panel-heading">
                <span class="step-index">01</span>
                <div>
                    <h2>Delivery details</h2>
                    <p>Where and how much?</p>
                </div>
            </div>

            <label>
                Number of containers
                <input type="number" name="quantity" min="1" max="100" value="{{ old('quantity', 2) }}" required>
                @error('quantity')
                    <small class="field-error">{{ $message }}</small>
                @enderror
            </label>

            <label>
                Delivery address
                <textarea name="delivery_address" rows="3" required>{{ old('delivery_address', auth()->user()->address) }}</textarea>
                @error('delivery_address')
                    <small class="field-error">{{ $message }}</small>
                @enderror
            </label>

            <label>
                <span class="label-title">Delivery notes <span class="optional">OPTIONAL</span></span>
                <textarea name="notes" rows="3" placeholder="Gate code, preferred drop-off spot…">{{ old('notes') }}</textarea>
                @error('notes')
                    <small class="field-error">{{ $message }}</small>
                @enderror
            </label>

            <div class="form-actions">
                <a class="text-link" href="{{ route('dashboard') }}">Cancel</a>
                <button class="button button-primary" type="submit">Submit order <span>→</span></button>
            </div>
        </form>

        <aside class="order-aside">
            <span class="eyebrow">WHAT HAPPENS NEXT</span>
            <ol class="next-steps">
                <li>
                    <span>1</span>
                    <div>
                        <strong>We confirm</strong>
                        <small>Our team reviews your order.</small>
                    </div>
                </li>
                <li>
                    <span>2</span>
                    <div>
                        <strong>We prepare</strong>
                        <small>Containers are cleaned and refilled.</small>
                    </div>
                </li>
                <li>
                    <span>3</span>
                    <div>
                        <strong>We deliver</strong>
                        <small>You’ll get an update when we’re on the way.</small>
                    </div>
                </li>
            </ol>

            <div class="aside-footnote">
                Need to change something? Add a note above and our team will take a look.
            </div>
        </aside>
    </div>
@endsection
@extends('layouts.app')

@section('title', 'Delivery riders')

@section('content')
    <div class="page-heading">
        <span class="eyebrow">DELIVERY SERVICE</span>
        <h1>Rider accounts</h1>
        <p>Create rider access, then assign riders to orders from the order editor.</p>
    </div>

    <section class="content-section">
        <div class="section-heading">
            <div>
                <span class="eyebrow">NEW RIDER</span>
                <h2>Create an account</h2>
            </div>
        </div>

        <form class="form-panel form-stack rider-create" method="POST" action="{{ route('admin.riders.store') }}">
            @csrf

            <div class="form-name-fields">
                <label>
                    First name
                    <input name="first_name" value="{{ old('first_name') }}" maxlength="80" required autocomplete="given-name">
                    @error('first_name') <span class="field-error">{{ $message }}</span> @enderror
                </label>
                <label>
                    <span class="label-title">Middle name <span class="optional">OPTIONAL</span></span>
                    <input name="middle_name" value="{{ old('middle_name') }}" maxlength="80" autocomplete="additional-name">
                    @error('middle_name') <span class="field-error">{{ $message }}</span> @enderror
                </label>
                <label>
                    Last name
                    <input name="last_name" value="{{ old('last_name') }}" maxlength="80" required autocomplete="family-name">
                    @error('last_name') <span class="field-error">{{ $message }}</span> @enderror
                </label>
            </div>

            <div class="administrator-fields">
                <label>
                    Email
                    <input type="email" name="email" value="{{ old('email') }}" maxlength="255" required autocomplete="email">
                    @error('email') <span class="field-error">{{ $message }}</span> @enderror
                </label>
                <label>
                    Phone <span class="optional">OPTIONAL</span>
                    <input type="tel" name="phone" value="{{ old('phone') }}" maxlength="40" autocomplete="tel">
                    @error('phone') <span class="field-error">{{ $message }}</span> @enderror
                </label>
                <label>
                    Password
                    <input type="password" name="password" minlength="8" required autocomplete="new-password">
                    @error('password') <span class="field-error">{{ $message }}</span> @enderror
                </label>
                <label>
                    Confirm password
                    <input type="password" name="password_confirmation" minlength="8" required autocomplete="new-password">
                </label>
            </div>

            <div class="form-actions">
                <button class="button button-primary" type="submit">Create rider</button>
            </div>
        </form>
    </section>

    <section class="content-section customer-directory">
        <div class="section-heading">
            <div>
                <span class="eyebrow">DELIVERY TEAM</span>
                <h2>Active riders</h2>
            </div>
            <span class="section-count">{{ $riders->total() }} total</span>
        </div>

        @if ($riders->isEmpty())
            <div class="empty-state compact-empty">
                <h3>No rider accounts yet.</h3>
                <p>Create an account above to start assigning deliveries.</p>
            </div>
        @else
            <div class="customer-list">
                @foreach ($riders as $rider)
                    <article class="customer-row">
                        <div class="customer-summary">
                            <span class="avatar">{{ strtoupper(substr($rider->name, 0, 1)) }}</span>
                            <div>
                                <strong>{{ $rider->name }}</strong>
                                <small>{{ $rider->email }} · {{ $rider->phone ?: 'No phone added' }}</small>
                            </div>
                            <span class="section-count">{{ $rider->assigned_deliveries_count }} assigned</span>
                        </div>
                    </article>
                @endforeach
            </div>
            <div class="pagination-wrap">{{ $riders->links() }}</div>
        @endif
    </section>
@endsection
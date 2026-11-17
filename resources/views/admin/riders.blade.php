@extends('layouts.app')

@section('title', 'Delivery riders')

@section('content')
    @php($createValues = old('editing_rider') ? [] : array_map(fn ($value) => is_string($value) ? $value : '', session()->getOldInput()))
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
                    <input name="first_name" value="{{ $createValues['first_name'] ?? '' }}" maxlength="80" required autocomplete="given-name">
                    @error('first_name') <span class="field-error">{{ $message }}</span> @enderror
                </label>
                <label>
                    <span class="label-title">Middle name <span class="optional">OPTIONAL</span></span>
                    <input name="middle_name" value="{{ $createValues['middle_name'] ?? '' }}" maxlength="80" autocomplete="additional-name">
                    @error('middle_name') <span class="field-error">{{ $message }}</span> @enderror
                </label>
                <label>
                    Last name
                    <input name="last_name" value="{{ $createValues['last_name'] ?? '' }}" maxlength="80" required autocomplete="family-name">
                    @error('last_name') <span class="field-error">{{ $message }}</span> @enderror
                </label>
            </div>

            <div class="administrator-fields">
                <label>
                    Email
                    <input type="email" name="email" value="{{ $createValues['email'] ?? '' }}" maxlength="255" required autocomplete="email">
                    @error('email') <span class="field-error">{{ $message }}</span> @enderror
                </label>
                <label>
                    Phone <span class="optional">OPTIONAL</span>
                    <input type="tel" name="phone" value="{{ $createValues['phone'] ?? '' }}" maxlength="40" autocomplete="tel">
                    @error('phone') <span class="field-error">{{ $message }}</span> @enderror
                </label>
                <x-password-field
                    name="password"
                    id="rider-password"
                    label="Password"
                    minlength="8"
                    autocomplete="new-password"
                    required
                />
                <x-password-field
                    name="password_confirmation"
                    id="rider-password-confirmation"
                    label="Confirm password"
                    toggle-label="password confirmation"
                    minlength="8"
                    autocomplete="new-password"
                    required
                />
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

        <p class="approval-note">Complete or reassign unfinished deliveries before removing a rider. Delivery history will be kept.</p>
        @error('rider')
            <p class="field-error" role="alert">{{ $message }}</p>
        @enderror

        @if ($riders->isEmpty())
            <div class="empty-state compact-empty">
                <h3>No rider accounts yet.</h3>
                <p>Create an account above to start assigning deliveries.</p>
            </div>
        @else
            <div class="customer-list rider-list">
                @foreach ($riders as $rider)
                    <article class="customer-row">
                        <div class="customer-summary">
                            <div>
                                <button class="rider-profile-trigger" type="button" data-rider-open="rider-profile-{{ $rider->id }}" aria-haspopup="dialog" aria-controls="rider-profile-{{ $rider->id }}">
                                    <strong>{{ $rider->name }}</strong>
                                    <small>{{ $rider->email ?: 'No email added' }} · {{ $rider->phone ?: 'No phone added' }}</small>
                                    <small>{{ $rider->assigned_deliveries_count }} assigned {{ \Illuminate\Support\Str::plural('delivery', $rider->assigned_deliveries_count) }}</small>
                                    <span>View rider details</span>
                                </button>
                            </div>
                            <form
                                method="POST"
                                action="{{ route('admin.riders.delete', $rider) }}"
                                data-confirm="Remove {{ $rider->name }}? They will no longer be able to sign in. Delivery history will be kept."
                            >
                                @csrf
                                @method('DELETE')
                                <button class="delete-button" type="submit" aria-label="Remove rider {{ $rider->name }}">Remove rider</button>
                            </form>
                        </div>
                    </article>
                    @include('admin.riders.profile-dialog', ['rider' => $rider])
                @endforeach
            </div>
            <div class="pagination-wrap">{{ $riders->links('layouts.partials.pagination') }}</div>
        @endif
    </section>
@endsection

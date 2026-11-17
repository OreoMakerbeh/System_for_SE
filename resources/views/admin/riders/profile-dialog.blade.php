@php
    $errorBag = 'riderUpdate'.$rider->id;
    $editing = $errors->getBag($errorBag)->any();
    $values = $editing ? session()->getOldInput() : $rider->only(['first_name', 'middle_name', 'last_name', 'email', 'phone', 'address']);
    $values = array_map(fn ($value) => is_string($value) ? $value : '', $values);
@endphp

<dialog id="rider-profile-{{ $rider->id }}" class="rider-profile-dialog" aria-labelledby="rider-profile-title-{{ $rider->id }}" data-rider-dialog @if ($editing) data-open-on-load @endif>
    <div class="rider-profile-heading">
        <div>
            <span class="eyebrow">RIDER INFORMATION</span>
            <h2 id="rider-profile-title-{{ $rider->id }}">{{ $rider->name }}</h2>
        </div>
        <button class="button button-small button-outline" type="button" data-rider-close autofocus>Close</button>
    </div>

    <div class="rider-profile-content">
        <nav class="rider-profile-shortcuts" aria-label="Rider information sections">
            <a href="#rider-edit-{{ $rider->id }}">Edit information</a>
            <a href="#rider-password-{{ $rider->id }}">Password</a>
            <a href="#rider-deliveries-{{ $rider->id }}">Delivery status</a>
        </nav>
        <div class="rider-profile-metrics" aria-label="Delivery summary">
            <div class="profile-metric"><span>Assigned</span><strong>{{ $rider->assigned_deliveries_count }}</strong></div>
            <div class="profile-metric"><span>Active</span><strong>{{ $rider->active_deliveries_count }}</strong></div>
            <div class="profile-metric"><span>Delivered</span><strong>{{ $rider->completed_deliveries_count }}</strong></div>
        </div>
        <p class="rider-profile-note">Joined {{ $rider->created_at->format('M d, Y') }}. Totals include completed and cancelled assignments.</p>

        <section id="rider-deliveries-{{ $rider->id }}" aria-labelledby="rider-deliveries-title-{{ $rider->id }}">
            <h3 id="rider-deliveries-title-{{ $rider->id }}">Recent deliveries</h3>
            <p class="rider-profile-note">Up to five recent, unarchived assignments. Refresh the page for the latest status.</p>
            @forelse ($rider->assignedDeliveries as $order)
                @include('admin.riders.delivery-progress', ['order' => $order])
            @empty
                <p class="rider-closed-note">No recent deliveries assigned.</p>
            @endforelse
        </section>

        <form id="rider-edit-{{ $rider->id }}" class="form-stack rider-profile-form" method="POST" action="{{ route('admin.riders.update', $rider) }}">
            @csrf
            @method('PATCH')
            <input type="hidden" name="editing_rider" value="{{ $rider->id }}">
            <h3>Edit rider information</h3>
            @if ($editing)
                <p class="flash-error" role="alert">{{ $errors->getBag($errorBag)->first() }}</p>
            @endif

            <div class="form-name-fields">
                @foreach (['first_name' => 'First name', 'middle_name' => 'Middle name (optional)', 'last_name' => 'Last name'] as $field => $label)
                    <label>
                        {{ $label }}
                        <input name="{{ $field }}" value="{{ $values[$field] ?? '' }}" maxlength="80" @required($field !== 'middle_name') @if ($errors->getBag($errorBag)->has($field)) aria-invalid="true" @endif>
                        @error($field, $errorBag)<small class="field-error">{{ $message }}</small>@enderror
                    </label>
                @endforeach
            </div>

            <p class="rider-profile-note">Keep at least one sign-in contact: mobile number or email.</p>
            <div class="form-pair">
                <label>
                    Mobile number
                    <input type="tel" name="phone" value="{{ $values['phone'] ?? '' }}" maxlength="40" autocomplete="off" @if ($errors->getBag($errorBag)->has('phone')) aria-invalid="true" @endif>
                    @error('phone', $errorBag)<small class="field-error">{{ $message }}</small>@enderror
                </label>
                <label>
                    Email
                    <input type="email" name="email" value="{{ $values['email'] ?? '' }}" maxlength="255" autocomplete="off" @if ($errors->getBag($errorBag)->has('email')) aria-invalid="true" @endif>
                    @error('email', $errorBag)<small class="field-error">{{ $message }}</small>@enderror
                </label>
            </div>
            <label>
                Address (optional)
                <textarea name="address" rows="2" maxlength="1000">{{ $values['address'] ?? '' }}</textarea>
                @error('address', $errorBag)<small class="field-error">{{ $message }}</small>@enderror
            </label>

            <fieldset id="rider-password-{{ $rider->id }}" class="rider-password-settings">
                <legend>Password configuration</legend>
                <p class="rider-profile-note">Leave both fields blank to keep the current password. A new password must have at least eight characters.</p>
                <div class="form-pair">
                    <x-password-field name="password" :id="'rider-new-password-'.$rider->id" label="New password" minlength="8" autocomplete="new-password" :error-bag="$errorBag" />
                    <x-password-field name="password_confirmation" :id="'rider-confirm-password-'.$rider->id" label="Confirm new password" toggle-label="new password confirmation" minlength="8" autocomplete="new-password" :error-bag="$errorBag" />
                </div>
            </fieldset>
            <div class="form-actions">
                <button class="button button-primary" type="submit">Save rider changes</button>
            </div>
        </form>
    </div>
</dialog>

<button class="profile-trigger" type="button" data-profile-open="customer-profile-{{ $customer->id }}" aria-haspopup="dialog" aria-controls="customer-profile-{{ $customer->id }}">
    <strong>{{ $customer->name }}</strong>
    <small class="table-subline">
        {{ $customer->phone ?: $customer->email ?: 'No contact added' }}
        @if ($customer->phone && $customer->email)
            · {{ $customer->email }}
        @endif
    </small>
    <span>View customer details</span>
</button>

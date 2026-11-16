<article class="customer-row">
    <div class="customer-card-head">
        <div class="customer-summary">
            <div>
                <strong>{{ $customer->name }}</strong>
                <small>
                    {{ $customer->phone ?: $customer->email }}
                    @if ($customer->phone && $customer->email)
                        · {{ $customer->email }}
                    @endif
                </small>
            </div>
        </div>

        <div class="customer-profile-summary">
            <div class="profile-metric">
                <span>Delivery address</span>
                <strong>{{ $customer->address }}</strong>
            </div>
            <div class="profile-metric">
                <span>Registered</span>
                <strong>{{ optional($customer->created_at)->format('M d, Y') ?? '—' }}</strong>
            </div>
            @if ($customer->declined_at !== null)
                <div class="profile-metric">
                    <span>Declined</span>
                    <strong>{{ $customer->declined_at->format('M d, Y · g:i A') }}</strong>
                </div>
            @endif
        </div>

        <div class="form-actions approval-actions">
            <form method="POST" action="{{ route('admin.approvals.approve', $customer) }}">
                @csrf
                @method('PATCH')
                <button class="button button-primary button-small" type="submit">Approve account</button>
            </form>
            @if ($customer->declined_at === null)
                <form method="POST" action="{{ route('admin.approvals.decline', $customer) }}">
                    @csrf
                    @method('PATCH')
                    <button class="button button-outline button-small" type="submit">Decline account</button>
                </form>
            @endif
        </div>
    </div>
</article>

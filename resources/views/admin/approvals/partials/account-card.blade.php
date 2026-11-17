<article class="customer-row">
    <div class="customer-card-head">
        <div class="customer-summary">
            <div>
                <strong>{{ $account->name }}</strong>
                <small>
                    {{ $account->phone ?: $account->email }}
                    @if ($account->phone && $account->email)
                        · {{ $account->email }}
                    @endif
                </small>
            </div>
        </div>

        <div class="customer-profile-summary">
            <div class="profile-metric">
                <span>Account type</span>
                <strong>{{ $account->role === 'rider' ? 'Delivery rider' : 'Customer' }}</strong>
            </div>
            <div class="profile-metric">
                <span>{{ $account->role === 'rider' ? 'Address' : 'Delivery address' }}</span>
                <strong>{{ $account->address }}</strong>
            </div>
            <div class="profile-metric">
                <span>Registered</span>
                <strong>{{ optional($account->created_at)->format('M d, Y') ?? '—' }}</strong>
            </div>
            @if ($account->declined_at !== null)
                <div class="profile-metric">
                    <span>Declined</span>
                    <strong>{{ $account->declined_at->format('M d, Y · g:i A') }}</strong>
                </div>
            @endif
        </div>

        <div class="form-actions approval-actions">
            <form method="POST" action="{{ route('admin.approvals.approve', $account) }}">
                @csrf
                @method('PATCH')
                <button class="button button-primary button-small" type="submit">Approve account</button>
            </form>
            @if ($account->declined_at === null)
                <form method="POST" action="{{ route('admin.approvals.decline', $account) }}">
                    @csrf
                    @method('PATCH')
                    <button class="button button-outline button-small" type="submit">Decline account</button>
                </form>
            @endif
        </div>
    </div>
</article>

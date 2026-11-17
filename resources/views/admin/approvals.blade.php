@extends('layouts.app')

@section('title', 'Account approvals')

@section('content')
    <div class="page-heading">
        <span class="eyebrow">ACCOUNT REVIEW</span>
        <h1>Account approvals.</h1>
        <p>Approve or decline new registrations before enabling customer or rider access.</p>
    </div>

    <section class="content-section customer-directory" aria-labelledby="pending-heading">
        <div class="section-heading">
            <div>
                <span class="eyebrow">PENDING ACCESS</span>
                <h2 id="pending-heading">New registrations</h2>
            </div>
            <span class="section-count">{{ $pendingAccounts->total() }} pending</span>
        </div>

        @if ($pendingAccounts->isEmpty())
            <div class="empty-state compact-empty">
                <h3>No accounts awaiting approval.</h3>
                <p>New customer and rider registrations will appear here.</p>
            </div>
        @else
            <div class="customer-list">
                @foreach ($pendingAccounts as $account)
                    @include('admin.approvals.partials.account-card', ['account' => $account])
                @endforeach
            </div>

            <div class="pagination-wrap">{{ $pendingAccounts->links('layouts.partials.pagination') }}</div>
        @endif
    </section>

    <section class="content-section customer-directory" aria-labelledby="declined-heading">
        <div class="section-heading">
            <div>
                <span class="eyebrow">ACCESS DECLINED</span>
                <h2 id="declined-heading">Declined registrations</h2>
            </div>
            <span class="section-count">{{ $declinedAccounts->total() }} declined</span>
        </div>
        <p class="approval-note">Declined customers and riders cannot sign in. You can approve a declined registration later.</p>

        @if ($declinedAccounts->isEmpty())
            <div class="empty-state compact-empty">
                <h3>No declined registrations.</h3>
                <p>Registrations you decline will appear here.</p>
            </div>
        @else
            <div class="customer-list">
                @foreach ($declinedAccounts as $account)
                    @include('admin.approvals.partials.account-card', ['account' => $account])
                @endforeach
            </div>
            <div class="pagination-wrap">{{ $declinedAccounts->links('layouts.partials.pagination') }}</div>
        @endif
    </section>
@endsection

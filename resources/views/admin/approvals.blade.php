@extends('layouts.app')

@section('title', 'Customer approvals')

@section('content')
    <div class="page-heading">
        <span class="eyebrow">ACCOUNT REVIEW</span>
        <h1>Customer approvals.</h1>
        <p>Approve or decline new registrations before enabling customer access.</p>
    </div>

    <section class="content-section customer-directory" aria-labelledby="pending-heading">
        <div class="section-heading">
            <div>
                <span class="eyebrow">PENDING ACCESS</span>
                <h2 id="pending-heading">New registrations</h2>
            </div>
            <span class="section-count">{{ $pendingCustomers->total() }} pending</span>
        </div>

        @if ($pendingCustomers->isEmpty())
            <div class="empty-state compact-empty">
                <h3>No accounts awaiting approval.</h3>
                <p>New customer registrations will appear here.</p>
            </div>
        @else
            <div class="customer-list">
                @foreach ($pendingCustomers as $customer)
                    @include('admin.approvals.partials.customer-card', ['customer' => $customer])
                @endforeach
            </div>

            <div class="pagination-wrap">{{ $pendingCustomers->links('layouts.partials.pagination') }}</div>
        @endif
    </section>

    <section class="content-section customer-directory" aria-labelledby="declined-heading">
        <div class="section-heading">
            <div>
                <span class="eyebrow">ACCESS DECLINED</span>
                <h2 id="declined-heading">Declined registrations</h2>
            </div>
            <span class="section-count">{{ $declinedCustomers->total() }} declined</span>
        </div>
        <p class="approval-note">Declined customers cannot sign in. You can approve a declined registration later.</p>

        @if ($declinedCustomers->isEmpty())
            <div class="empty-state compact-empty">
                <h3>No declined registrations.</h3>
                <p>Registrations you decline will appear here.</p>
            </div>
        @else
            <div class="customer-list">
                @foreach ($declinedCustomers as $customer)
                    @include('admin.approvals.partials.customer-card', ['customer' => $customer])
                @endforeach
            </div>
            <div class="pagination-wrap">{{ $declinedCustomers->links('layouts.partials.pagination') }}</div>
        @endif
    </section>
@endsection

@extends('layouts.app')

@section('title', 'Customer approvals')

@section('content')
    <div class="page-heading">
        <span class="eyebrow">ACCOUNT REVIEW</span>
        <h1>Customer approvals.</h1>
        <p>Review new registrations before enabling customer access.</p>
    </div>

    <section class="content-section customer-directory">
        <div class="section-heading">
            <div>
                <span class="eyebrow">PENDING ACCESS</span>
                <h2>New registrations</h2>
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
                    <article class="customer-row">
                        <div class="customer-card-head">
                            <div class="customer-summary">
                                <span class="avatar">{{ strtoupper(substr($customer->name, 0, 1)) }}</span>
                                <div>
                                    <strong>{{ $customer->name }}</strong>
                                    <small>{{ $customer->email }} · {{ $customer->phone }}</small>
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
                            </div>

                            <form method="POST" action="{{ route('admin.approvals.approve', $customer) }}">
                                @csrf
                                @method('PATCH')
                                <button class="button button-primary button-small" type="submit">Approve account</button>
                            </form>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="pagination-wrap">{{ $pendingCustomers->links() }}</div>
        @endif
    </section>
@endsection
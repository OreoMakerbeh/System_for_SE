@extends('layouts.app')

@section('title', 'Customers')

@section('content')
    <div class="page-heading">
        <span class="eyebrow">CUSTOMER DIRECTORY</span>
        <h1>People we serve.</h1>
        <p>Update contact details and keep delivery information current.</p>
    </div>

    <section class="content-section customer-directory">
        <div class="section-heading">
            <div>
                <span class="eyebrow">ACCOUNTS</span>
                <h2>Customers</h2>
            </div>
            <span class="section-count">{{ $customers->total() }} total</span>
        </div>

        @if ($customers->isEmpty())
            <div class="empty-state compact-empty">
                <h3>No customers yet.</h3>
                <p>New registrations will show up here.</p>
            </div>
        @else
            <div class="customer-list">
                @foreach ($customers as $customer)
                    <article class="customer-row">
                        <div class="customer-card-head">
                            <div class="customer-summary">
                                <span class="avatar">{{ strtoupper(substr($customer->name, 0, 1)) }}</span>
                                <div>
                                    <strong>{{ $customer->name }}</strong>
                                    <small>
                                        {{ $customer->email }} · {{ $customer->orders_count }}
                                        {{ \Illuminate\Support\Str::plural('order', $customer->orders_count) }}
                                    </small>
                                </div>

                                <form
                                    method="POST"
                                    action="{{ route('admin.customers.delete', $customer) }}"
                                    onsubmit="return confirm('Delete this customer and their order history?')"
                                >
                                    @csrf
                                    @method('DELETE')
                                    <button
                                        class="delete-button"
                                        type="submit"
                                        title="Delete customer"
                                        aria-label="Delete {{ $customer->name }}"
                                    >
                                        Delete
                                    </button>
                                </form>
                            </div>

                            <div class="customer-profile-summary">
                                <div class="profile-metric">
                                    <span>Joined</span>
                                    <strong>{{ optional($customer->created_at)->format('M d, Y') ?? '—' }}</strong>
                                </div>
                                <div class="profile-metric">
                                    <span>Orders</span>
                                    <strong>{{ $customer->orders_count }}</strong>
                                </div>
                                <div class="profile-metric">
                                    <span>Items ordered</span>
                                    <strong>{{ (int) ($customer->orders_sum_quantity ?? 0) }}</strong>
                                </div>
                                <div class="profile-metric">
                                    <span>Last order</span>
                                    <strong>
                                        {{ $customer->orders_max_created_at
                                            ? \Illuminate\Support\Carbon::parse($customer->orders_max_created_at)->format('M d, Y')
                                            : '—' }}
                                    </strong>
                                </div>
                            </div>
                        </div>

                        <form class="customer-edit" method="POST" action="{{ route('admin.customers.update', $customer) }}">
                            @csrf
                            @method('PATCH')

                            <div class="form-name-fields">
                                <label>
                                    First name
                                    <input name="first_name" value="{{ $customer->first_name }}" maxlength="80" required autocomplete="given-name">
                                    @error('first_name')
                                        <small class="field-error">{{ $message }}</small>
                                    @enderror
                                </label>

                                <label>
                                    <span class="label-title">Middle name <span class="optional">OPTIONAL</span></span>
                                    <input name="middle_name" value="{{ $customer->middle_name }}" maxlength="80" autocomplete="additional-name">
                                    @error('middle_name')
                                        <small class="field-error">{{ $message }}</small>
                                    @enderror
                                </label>

                                <label>
                                    Last name
                                    <input name="last_name" value="{{ $customer->last_name }}" maxlength="80" required autocomplete="family-name">
                                    @error('last_name')
                                        <small class="field-error">{{ $message }}</small>
                                    @enderror
                                </label>
                            </div>

                            <label>
                                Phone
                                <input name="phone" value="{{ $customer->phone }}" required>
                            </label>

                            <label>
                                Address
                                <input name="address" value="{{ $customer->address }}" required>
                            </label>

                            <button class="button button-small button-outline" type="submit">Save changes</button>
                        </form>
                    </article>
                @endforeach
            </div>

            <div class="pagination-wrap">{{ $customers->links() }}</div>
        @endif
    </section>
@endsection

@extends('layouts.app')

@section('title', 'Orders')

@section('content')
    <div class="page-heading">
        <span class="eyebrow">FULFILLMENT CONTROL</span>
        <h1>All orders</h1>
        <p>Review customer requests and move each delivery through its next step.</p>
    </div>

    <section class="content-section">
        <div class="section-heading">
            <div>
                <span class="eyebrow">ORDER QUEUE</span>
                <h2>{{ $orders->total() }} {{ \Illuminate\Support\Str::plural('order', $orders->total()) }}</h2>
            </div>
            <form method="GET" action="{{ route('admin.orders') }}" class="filter-form">
                <label for="status-filter">Filter status</label>
                <select id="status-filter" name="status" onchange="this.form.submit()">
                    <option value="">All statuses</option>
                    @foreach ($statuses as $key => $label)
                        <option value="{{ $key }}" @selected($selectedStatus === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </form>
        </div>

        @if ($orders->isEmpty())
            <div class="empty-state compact-empty">
                <h3>No orders in this view.</h3>
                <p>Try another status filter or check back when orders arrive.</p>
            </div>
        @else
            <div class="table-wrap">
                <table class="data-table admin-order-table">
                    <thead>
                        <tr>
                            <th>ORDER</th>
                            <th>CUSTOMER</th>
                            <th>DELIVERY</th>
                            <th>QUANTITY</th>
                            <th>PLACED</th>
                            <th>STATUS</th>
                            <th>UPDATE</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($orders as $order)
                            <tr>
                                <td class="order-number">#{{ $order->display_number }}</td>
                                <td>
                                    <strong>{{ $order->user->name }}</strong>
                                    <small class="table-subline">
                                        {{ $order->user->email }} · {{ $order->user->phone }}
                                    </small>
                                    <small class="table-subline">Rider: {{ $order->rider?->name ?? 'Unassigned' }}</small>
                                </td>
                                <td class="address-cell" title="{{ $order->delivery_address }}">
                                    {{ $order->delivery_address }}
                                </td>
                                <td>{{ $order->quantity }} containers</td>
                                <td>{{ $order->created_at->format('M j, Y') }}</td>
                                <td>
                                    <span class="status-pill status-{{ $order->status }}">
                                        <i></i>{{ $statuses[$order->status] }}
                                    </span>
                                </td>
                                <td>
                                    @if ($order->status === 'cancelled')
                                        <span class="table-subline">Cancelled by customer</span>
                                    @else
                                        <div class="admin-order-actions">
                                            <a class="button button-small button-outline" href="{{ route('admin.orders.edit', $order) }}">Edit</a>
                                            <form
                                                method="POST"
                                                action="{{ route('admin.orders.update', $order) }}"
                                                class="status-form"
                                            >
                                                @csrf
                                                @method('PATCH')

                                                <select name="status" aria-label="Update status for order {{ $order->display_number }}">
                                                    @foreach ($statuses as $key => $label)
                                                        @continue($key === 'cancelled')
                                                        <option value="{{ $key }}" @selected($order->status === $key)>
                                                            {{ $label }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <button class="button button-small button-outline" type="submit">Update status</button>
                                            </form>
                                            @if ($order->status === 'delivered')
                                                <form method="POST" action="{{ route('admin.orders.archive', $order) }}" onsubmit="return confirm('Move order #{{ $order->display_number }} to Costumer Data?')">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button class="button button-small button-archive" type="submit" aria-label="Archive delivered order {{ $order->display_number }}">Archive</button>
                                                </form>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                            </tr>

                            @if ($order->notes)
                                <tr class="order-note-row">
                                    <td></td>
                                    <td colspan="6">
                                        <span class="delivery-note-label">DELIVERY NOTE</span>
                                        <span>{{ $order->notes }}</span>
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="pagination-wrap">{{ $orders->links() }}</div>
        @endif
    </section>
@endsection

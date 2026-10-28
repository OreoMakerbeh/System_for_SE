@extends('layouts.app')

@section('title', 'Operations')

@section('content')
    <div class="page-heading">
        <span class="eyebrow">OPERATIONS OVERVIEW</span>
        <h1>Good work, {{ explode(' ', auth()->user()->name)[0] }}.</h1>
        <p>A clear view of today’s customer and delivery activity.</p>
    </div>

    <section class="stats-grid admin-stats">
        <article class="stat-panel">
            <span class="stat-label">ALL ORDERS</span>
            <strong>{{ str_pad($orderCount, 2, '0', STR_PAD_LEFT) }}</strong>
            <small>orders placed</small>
        </article>
        <article class="stat-panel stat-accent">
            <span class="stat-label">NEEDS ATTENTION</span>
            <strong>{{ str_pad($openCount, 2, '0', STR_PAD_LEFT) }}</strong>
            <small>not yet delivered</small>
        </article>
        <article class="stat-panel">
            <span class="stat-label">CUSTOMERS</span>
            <strong>{{ str_pad($customerCount, 2, '0', STR_PAD_LEFT) }}</strong>
            <small>registered accounts</small>
        </article>
        <article class="stat-panel stat-light">
            <span class="stat-label">DELIVERED</span>
            <strong>{{ str_pad($deliveredCount, 2, '0', STR_PAD_LEFT) }}</strong>
            <small>completed orders</small>
        </article>
    </section>

    <section class="content-section" id="orders">
        <div class="section-heading">
            <div>
                <span class="eyebrow">FULFILLMENT QUEUE</span>
                <h2>Recent orders</h2>
            </div>
            <div class="heading-actions">
                <a class="text-link" href="{{ route('admin.orders') }}">All orders <span>→</span></a>
                <a class="text-link" href="{{ route('admin.customers') }}">Manage customers <span>→</span></a>
            </div>
        </div>

        @if ($orders->isEmpty())
            <div class="empty-state compact-empty">
                <h3>No orders yet.</h3>
                <p>New customer orders will appear here.</p>
            </div>
        @else
            <div class="table-wrap">
                <table class="data-table admin-order-table">
                    <thead>
                        <tr>
                            <th>ORDER</th>
                            <th>CUSTOMER</th>
                            <th>PLACED</th>
                            <th>QUANTITY</th>
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
                                    <small class="table-subline">{{ $order->user->phone }}</small>
                                </td>
                                <td>{{ $order->created_at->format('M j, Y') }}</td>
                                <td>{{ $order->quantity }} containers</td>
                                <td>
                                    <span class="status-pill status-{{ $order->status }}">
                                        <i></i>{{ \App\Models\Order::STATUSES[$order->status] }}
                                    </span>
                                </td>
                                <td>
                                    @if ($order->status === 'cancelled')
                                        <span class="table-subline">Cancelled by customer</span>
                                    @else
                                        <div class="admin-order-actions">
                                            <a class="button button-small button-outline" href="{{ route('admin.orders.edit', $order) }}">Edit</a>
                                            <form method="POST" action="{{ route('admin.orders.update', $order) }}" class="status-form">
                                                @csrf
                                                @method('PATCH')
                                                <select name="status" aria-label="Update status for order {{ $order->display_number }}">
                                                    @foreach (\App\Models\Order::STATUSES as $key => $label)
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
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection

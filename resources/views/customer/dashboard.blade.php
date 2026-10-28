@extends('layouts.app')

@section('title', 'Overview')

@section('content')
    <div class="page-heading heading-row">
        <div>
            <span class="eyebrow">YOUR REFILL ROUTINE</span>
            <h1>
                Good {{ now()->hour < 12 ? 'morning' : (now()->hour < 17 ? 'afternoon' : 'evening') }},
                {{ auth()->user()->first_name ?: explode(' ', auth()->user()->name)[0] }}.
            </h1>
            <p>Here’s the latest on your water deliveries.</p>
        </div>
        <a class="button button-primary" href="{{ route('orders.create') }}">
            <span class="button-plus">＋</span> New order
        </a>
    </div>

    <section class="stats-grid customer-stats">
        <article class="stat-panel">
            <span class="stat-label">IN PROGRESS</span>
            <strong>{{ str_pad($activeCount, 2, '0', STR_PAD_LEFT) }}</strong>
            <small>orders on the way</small>
        </article>
        <article class="stat-panel stat-accent">
            <span class="stat-label">DELIVERED</span>
            <strong>{{ str_pad($deliveredCount, 2, '0', STR_PAD_LEFT) }}</strong>
            <small>orders completed</small>
        </article>
        <article class="welcome-panel">
            <span class="eyebrow">A NOTE FROM US</span>
            <p>Running low? Schedule your next refill and we’ll bring it right to your door.</p>
            <a href="{{ route('orders.create') }}">Place an order <span>→</span></a>
        </article>
    </section>

    @if ($deliveryAlerts->isNotEmpty())
        <dialog class="delivery-popup" open aria-labelledby="delivery-popup-heading">
            <div class="delivery-popup-content">
                <span class="notification-mark" aria-hidden="true">✓</span>
                <span class="eyebrow">DELIVERY UPDATE</span>
                <h2 id="delivery-popup-heading">
                    {{ $deliveryAlerts->count() === 1 ? 'Your order has arrived' : 'Your orders have arrived' }}
                </h2>
                <div class="delivery-popup-list">
                    @foreach ($deliveryAlerts as $alert)
                        <a class="delivery-popup-order" href="{{ route('orders.show', $alert->data['order_id']) }}">
                            <span>{{ $alert->data['message'] }}</span>
                            <span class="notification-link">View order <span aria-hidden="true">→</span></span>
                        </a>
                    @endforeach
                </div>
                <form method="dialog">
                    <button class="button button-primary delivery-popup-dismiss" type="submit">Got it</button>
                </form>
            </div>
        </dialog>
    @endif

    @if ($notifications->isNotEmpty())
        <section class="content-section notification-section" aria-labelledby="updates-heading">
            <div class="section-heading">
                <div>
                    <span class="eyebrow">DELIVERY UPDATES</span>
                    <h2 id="updates-heading">Notifications</h2>
                </div>
            </div>
            <div class="notification-list">
                @foreach ($notifications as $notification)
                    <div class="delivery-notification">
                        <span class="notification-mark" aria-hidden="true">✓</span>
                        <span class="notification-copy">
                            <strong>{{ $notification->data['message'] }}</strong>
                            <small>{{ $notification->created_at->diffForHumans() }}</small>
                        </span>
                        <div class="notification-actions">
                            @if ($notification->data['order_id'] ?? false)
                                <a class="notification-link" href="{{ route('orders.show', $notification->data['order_id']) }}">View order <span aria-hidden="true">→</span></a>
                            @endif
                            <form class="notification-delete-form" method="POST" action="{{ route('notifications.destroy', $notification->id) }}" onsubmit="return confirm('Delete this notification?')">
                                @csrf
                                @method('DELETE')
                                <button class="delete-order-button" type="submit">Delete</button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    <section class="content-section" id="orders">
        <div class="section-heading">
            <div>
                <span class="eyebrow">YOUR ACTIVITY</span>
                <h2>Recent orders</h2>
            </div>
            <span class="section-count">{{ $orders->total() }} total</span>
        </div>

        @if ($orders->isEmpty())
            <div class="empty-state">
                <span class="empty-mark">＋</span>
                <h3>Your first refill starts here.</h3>
                <p>Create an order and we’ll take it from there.</p>
                <a class="button button-outline" href="{{ route('orders.create') }}">
                    Place your first order <span>→</span>
                </a>
            </div>
        @else
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ORDER</th>
                            <th>PLACED</th>
                            <th>QUANTITY</th>
                            <th>DELIVERY ADDRESS</th>
                            <th>STATUS</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($orders as $order)
                            <tr>
                                <td class="order-number">#{{ $order->display_number }}</td>
                                <td>{{ $order->created_at->format('M j, Y') }}</td>
                                <td>{{ $order->quantity }} containers</td>
                                <td class="address-cell">{{ $order->delivery_address }}</td>
                                <td>
                                    <span class="status-pill status-{{ $order->status }}">
                                        <i></i>{{ \App\Models\Order::STATUSES[$order->status] }}
                                    </span>
                                </td>
                                <td class="order-actions">
                                    <a class="text-link" href="{{ route('orders.show', $order) }}" aria-label="Track order {{ $order->display_number }}">
                                        Track <span>→</span>
                                    </a>
                                    @if ($order->status === 'pending')
                                        <form method="POST" action="{{ route('orders.cancel', $order) }}" onsubmit="return confirm('Cancel this pending order?')">
                                            @csrf
                                            @method('PATCH')
                                            <button class="text-link cancel-order-link" type="submit">Cancel</button>
                                        </form>
                                    @endif
                                    @if (in_array($order->status, ['delivered', 'cancelled'], true) && $order->archived_at === null)
                                        <form method="POST" action="{{ route('orders.destroy', $order) }}" onsubmit="return confirm('Permanently delete order #{{ $order->display_number }}?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="delete-order-button" type="submit">Delete</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="pagination-wrap">{{ $orders->links() }}</div>
        @endif
    </section>
@endsection

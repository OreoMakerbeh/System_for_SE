@extends('layouts.app')

@section('title', 'Order #'.$order->display_number)

@section('content')
    <div class="page-heading heading-row">
        <div>
            <span class="eyebrow">ORDER TRACKING</span>
            <h1>Order #{{ $order->display_number }}</h1>
            <p>Placed {{ $order->created_at->format('F j, Y') }}</p>
        </div>
        <a class="text-link" href="{{ route('dashboard') }}">← All orders</a>
    </div>

    <section class="tracking-panel">
        <div class="tracking-top">
            <div>
                <span class="eyebrow">CURRENT STATUS</span>
                <h2>{{ \App\Models\Order::STATUSES[$order->status] }}</h2>
            </div>
            <span class="status-pill status-{{ $order->status }}">
                <i></i>{{ \App\Models\Order::STATUSES[$order->status] }}
            </span>
        </div>

        @if ($order->status === 'cancelled')
            <div class="order-cancelled-message" role="status">
                This order was cancelled and will not be delivered.
            </div>
        @else
            @php($statusKeys = array_keys(\App\Models\Order::STATUSES))
            @php($statusKeys = array_slice($statusKeys, 0, array_search('cancelled', $statusKeys, true)))
            @php($currentIndex = array_search($order->status, $statusKeys, true))

            <ol class="progress-track" style="--progress: {{ $currentIndex > 0 ? ($currentIndex / (count($statusKeys) - 1)) * 100 : 0 }}%">
                @foreach ($statusKeys as $stepIndex => $key)
                    <li class="{{ $stepIndex < $currentIndex ? 'is-done' : '' }} {{ $stepIndex === $currentIndex ? 'is-current' : '' }} {{ $key === 'pending' && $order->status === 'pending' ? 'is-awaiting-confirmation' : '' }} {{ $key === 'delivered' && $order->status === 'delivered' ? 'is-delivered' : '' }}">
                        <span class="progress-dot">
                            {{ $stepIndex < $currentIndex ? '✓' : str_pad($stepIndex + 1, 2, '0', STR_PAD_LEFT) }}
                        </span>
                        <span>{{ \App\Models\Order::STATUSES[$key] }}</span>
                    </li>
                @endforeach
            </ol>

            @if ($order->status === 'pending')
                <form method="POST" action="{{ route('orders.cancel', $order) }}" onsubmit="return confirm('Cancel this pending order?')">
                    @csrf
                    @method('PATCH')
                    <button class="button button-small button-outline cancel-order-button" type="submit">Cancel order</button>
                </form>
            @endif
        @endif

        @if (in_array($order->status, ['delivered', 'cancelled'], true) && $order->archived_at === null)
            <form method="POST" action="{{ route('orders.destroy', $order) }}" onsubmit="return confirm('Permanently delete order #{{ $order->display_number }}?')">
                @csrf
                @method('DELETE')
                <button class="button button-small button-outline delete-order-detail-button" type="submit">Delete order</button>
            </form>
        @endif

        @if ($order->rider || $order->deliveryUpdates->isNotEmpty())
            <section class="delivery-timeline" aria-labelledby="delivery-timeline-heading">
                <div class="timeline-heading">
                    <div>
                        <span class="eyebrow">LIVE DELIVERY</span>
                        <h3 id="delivery-timeline-heading">Delivery updates</h3>
                    </div>
                    @if ($order->rider)
                        <span class="timeline-rider">Rider: {{ $order->rider->name }}</span>
                    @endif
                </div>

                @if ($order->deliveryUpdates->isEmpty())
                    <p class="timeline-empty">Your rider will post updates as the order moves.</p>
                @else
                    <ol class="delivery-events">
                        @foreach ($order->deliveryUpdates->reverse() as $update)
                            <li>
                                <span class="event-mark" aria-hidden="true"></span>
                                <div class="event-copy">
                                    <strong>{{ \App\Models\DeliveryUpdate::MILESTONES[$update->status] }}</strong>
                                    <small>{{ $update->created_at->format('M j, Y · g:i A') }} · {{ $update->rider->name }}</small>
                                    @if ($update->message)
                                        <p>{{ $update->message }}</p>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ol>
                @endif
            </section>
        @endif

        <div class="tracking-details">
            <div>
                <span class="eyebrow">DELIVERY ADDRESS</span>
                <p>{{ $order->delivery_address }}</p>
            </div>
            <div>
                <span class="eyebrow">QUANTITY</span>
                <p>{{ $order->quantity }} containers</p>
            </div>
            @if ($order->notes)
                <div>
                    <span class="eyebrow">DELIVERY NOTES</span>
                    <p>{{ $order->notes }}</p>
                </div>
            @endif
        </div>
    </section>
@endsection

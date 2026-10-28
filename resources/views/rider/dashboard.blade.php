@extends('layouts.app')

@section('title', 'My deliveries')

@section('content')
    <div class="page-heading">
        <span class="eyebrow">DELIVERY SERVICE</span>
        <h1>My deliveries</h1>
        <p>{{ $activeCount }} active {{ \Illuminate\Support\Str::plural('delivery', $activeCount) }} assigned to you.</p>
    </div>

    <section class="content-section">
        <div class="section-heading">
            <div>
                <span class="eyebrow">YOUR ROUTE</span>
                <h2>{{ $orders->total() }} assigned {{ \Illuminate\Support\Str::plural('order', $orders->total()) }}</h2>
            </div>
        </div>

        @if ($orders->isEmpty())
            <div class="empty-state compact-empty">
                <h3>No deliveries assigned.</h3>
                <p>New assignments from the station will appear here.</p>
            </div>
        @else
            <div class="rider-delivery-list">
                @foreach ($orders as $order)
                    @php($latestUpdate = $order->deliveryUpdates->first())
                    @php($milestoneKeys = array_keys(\App\Models\DeliveryUpdate::MILESTONES))
                    @php($latestMilestoneIndex = $latestUpdate ? array_search($latestUpdate->status, $milestoneKeys, true) : -1)
                    @php($deliveryProgress = $latestMilestoneIndex > 0 ? ($latestMilestoneIndex / (count($milestoneKeys) - 1)) * 100 : 0)
                    @php($nextMilestoneIndex = $latestUpdate ? array_search($latestUpdate->status, $milestoneKeys, true) + 1 : 0)
                    @php($nextMilestone = $milestoneKeys[$nextMilestoneIndex] ?? null)

                    <article class="rider-delivery">
                        <div class="rider-delivery-heading">
                            <div>
                                <span class="eyebrow">ORDER #{{ $order->display_number }}</span>
                                <h3>{{ $order->user->name }}</h3>
                            </div>
                            <span class="status-pill status-{{ $order->status }}">
                                <i></i>{{ \App\Models\Order::STATUSES[$order->status] }}
                            </span>
                        </div>

                        <div class="rider-delivery-details">
                            <div>
                                <span class="eyebrow">DROP-OFF</span>
                                <p>{{ $order->delivery_address }}</p>
                                @if ($order->user->phone)
                                    <small>{{ $order->user->phone }}</small>
                                @endif
                            </div>
                            <div>
                                <span class="eyebrow">ORDER</span>
                                <p>{{ $order->quantity }} containers</p>
                                @if ($order->notes)
                                    <small>Note: {{ $order->notes }}</small>
                                @endif
                            </div>
                        </div>

                        <div class="rider-delivery-progress" aria-label="Delivery progress">
                            <ol class="rider-progress-track" style="--delivery-progress: {{ $deliveryProgress }}%">
                                @foreach ($milestoneKeys as $stepIndex => $milestone)
                                    <li class="{{ $stepIndex < $latestMilestoneIndex ? 'is-done' : '' }} {{ $stepIndex === $latestMilestoneIndex ? 'is-current' : '' }} {{ $latestMilestoneIndex === -1 && $stepIndex === 0 ? 'is-next' : '' }}">
                                        <span class="rider-progress-dot">
                                            {{ $stepIndex < $latestMilestoneIndex ? '✓' : str_pad($stepIndex + 1, 2, '0', STR_PAD_LEFT) }}
                                        </span>
                                        <span>{{ \App\Models\DeliveryUpdate::MILESTONES[$milestone] }}</span>
                                    </li>
                                @endforeach
                            </ol>
                        </div>

                        @if (in_array($order->status, ['cancelled', 'delivered'], true))
                            <p class="rider-closed-note">
                                {{ $order->status === 'cancelled' ? 'This order was cancelled.' : 'Delivery completed.' }}
                            </p>
                        @elseif (in_array($order->status, ['processing', 'out_for_delivery'], true) && $nextMilestone)
                            <form class="rider-update-form" method="POST" action="{{ route('rider.orders.updates.store', $order) }}">
                                @csrf
                                <input type="hidden" name="status" value="{{ $nextMilestone }}">
                                <div>
                                    <span class="eyebrow">NEXT MILESTONE</span>
                                    <strong>{{ \App\Models\DeliveryUpdate::MILESTONES[$nextMilestone] }}</strong>
                                </div>
                                <label>
                                    <span class="label-title">Update for customer <span class="optional">OPTIONAL</span></span>
                                    <textarea name="message" maxlength="500" rows="2" placeholder="Add a short delivery note"></textarea>
                                </label>
                                <button class="button button-primary" type="submit">Post update</button>
                            </form>
                        @else
                            <p class="rider-closed-note">Updates will be available when order processing is complete.</p>
                        @endif

                        @if ($order->deliveryUpdates->isNotEmpty())
                            <ol class="rider-recent-updates">
                                @foreach ($order->deliveryUpdates->take(3) as $update)
                                    <li>
                                        <strong>{{ \App\Models\DeliveryUpdate::MILESTONES[$update->status] }}</strong>
                                        <small>{{ $update->created_at->format('M j · g:i A') }}</small>
                                    </li>
                                @endforeach
                            </ol>
                        @endif
                    </article>
                @endforeach
            </div>
            <div class="pagination-wrap">{{ $orders->links() }}</div>
        @endif
    </section>

    <section class="content-section available-deliveries">
        <div class="section-heading">
            <div>
                <span class="eyebrow">OPEN ORDERS</span>
                <h2>{{ $availableOrders->total() }} available {{ \Illuminate\Support\Str::plural('delivery', $availableOrders->total()) }}</h2>
            </div>
            <span class="section-count">Processing orders without a rider</span>
        </div>

        @if ($availableOrders->isEmpty())
            <div class="empty-state compact-empty">
                <h3>No open deliveries right now.</h3>
                <p>Orders become available after the station finishes processing them.</p>
            </div>
        @else
            <div class="rider-delivery-list">
                @foreach ($availableOrders as $order)
                    <article class="rider-delivery available-delivery">
                        <div class="rider-delivery-heading">
                            <div>
                                <span class="eyebrow">ORDER #{{ $order->display_number }}</span>
                                <h3>{{ $order->user->name }}</h3>
                            </div>
                            <span class="status-pill status-processing"><i></i>Ready for pickup</span>
                        </div>
                        <div class="rider-delivery-details">
                            <div>
                                <span class="eyebrow">DROP-OFF</span>
                                <p>{{ $order->delivery_address }}</p>
                                @if ($order->user->phone)
                                    <small>{{ $order->user->phone }}</small>
                                @endif
                            </div>
                            <div>
                                <span class="eyebrow">ORDER</span>
                                <p>{{ $order->quantity }} containers</p>
                                @if ($order->notes)
                                    <small>Note: {{ $order->notes }}</small>
                                @endif
                            </div>
                        </div>
                        <form class="rider-claim-form" method="POST" action="{{ route('rider.orders.claim', $order) }}">
                            @csrf
                            @method('PATCH')
                            <span>Claim this delivery to add it to your route.</span>
                            <button class="button button-primary" type="submit">Claim delivery</button>
                        </form>
                    </article>
                @endforeach
            </div>
            <div class="pagination-wrap">{{ $availableOrders->links() }}</div>
        @endif
    </section>
@endsection
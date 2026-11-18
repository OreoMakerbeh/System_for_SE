@php
    $milestones = \App\Models\DeliveryUpdate::MILESTONES;
    $latestUpdate = $order->deliveryUpdates->first();
    $currentStep = array_search($latestUpdate?->status, array_keys($milestones), true);
    if ($order->status === 'delivered') {
        $currentStep = 3;
    } elseif ($order->status === 'out_for_delivery') {
        $currentStep = 2;
    }
    $progress = $currentStep === false ? 0 : $currentStep / 3 * 100;
    $closed = in_array($order->status, ['delivered', 'cancelled'], true);
@endphp

<article @class(['profile-order', 'is-closed' => $closed])>
    <div class="profile-order-heading">
        <div>
            @if ($order->status === 'cancelled')
                <strong>Order #{{ $order->display_number }}</strong>
            @else
                <a href="{{ route('admin.orders.edit', $order) }}">Order #{{ $order->display_number }}</a>
            @endif
            <p>{{ $order->user->name }} · {{ $order->quantity }} containers · {{ $order->container_type_label }}</p>
        </div>
        <span class="status-pill status-{{ $order->status }}">{{ \App\Models\Order::STATUSES[$order->status] }}</span>
    </div>
    @if ($order->status === 'cancelled')
        <p class="rider-closed-note">This delivery was cancelled.</p>
    @else
        <ol class="rider-progress-track" style="--delivery-progress: {{ $progress }}%" aria-label="Delivery progress">
            @foreach ($milestones as $milestone => $label)
                <li @class(['is-done' => $currentStep !== false && $loop->index < $currentStep, 'is-current' => $loop->index === $currentStep, 'is-next' => $currentStep === false && $loop->first]) @if ($loop->index === $currentStep) aria-current="step" @endif>
                    <span class="rider-progress-dot">{{ $loop->iteration }}</span>
                    <span>{{ $label }}</span>
                </li>
            @endforeach
        </ol>
        @if ($currentStep === false)
            <p class="profile-note">Awaiting the rider’s first delivery update.</p>
        @endif
    @endif
    @if ($latestUpdate)
        <p class="profile-note">Last update: {{ $latestUpdate->created_at->format('M d · g:i A') }}@if ($latestUpdate->message) · {{ $latestUpdate->message }}@endif</p>
    @endif
</article>

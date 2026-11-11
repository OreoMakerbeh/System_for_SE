<div class="order-status-actions" aria-label="Update order #{{ $order->display_number }} status">
    @foreach ($order->availableStatusOptions() as $key => $label)
        <form method="POST" action="{{ route('admin.orders.update', $order) }}">
            @csrf
            @method('PATCH')
            <button
                class="order-process-button {{ $order->status === $key ? 'is-current' : '' }}"
                type="submit"
                name="status"
                value="{{ $key }}"
                @disabled($order->status === $key)
                @if ($order->status === $key) aria-current="step" @endif
            >
                {{ $label }}
            </button>
        </form>
    @endforeach

    @if ($order->status === 'delivered')
        <form
            method="POST"
            action="{{ route('admin.orders.archive', $order) }}"
            data-confirm="Move order #{{ $order->display_number }} to Customer Data?"
        >
            @csrf
            @method('PATCH')
            <button
                class="order-process-button order-archive-button"
                type="submit"
                aria-label="Archive delivered order {{ $order->display_number }}"
            >Archive</button>
        </form>
    @endif
</div>

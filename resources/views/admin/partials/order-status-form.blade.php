<form method="POST" action="{{ route('admin.orders.update', $order) }}" class="status-form">
    @csrf
    @method('PATCH')

    <select name="status" aria-label="Update status for order {{ $order->display_number }}">
        @foreach ($order->availableStatusOptions() as $status => $label)
            <option value="{{ $status }}" @selected($order->status === $status)>
                {{ $label }}
            </option>
        @endforeach
    </select>
    <button class="button button-small button-outline" type="submit">Update status</button>
</form>

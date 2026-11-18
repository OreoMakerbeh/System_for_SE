<dialog id="customer-profile-{{ $customer->id }}" class="profile-dialog" aria-labelledby="customer-profile-title-{{ $customer->id }}" data-profile-dialog>
    <div class="profile-heading">
        <div>
            <span class="eyebrow">CUSTOMER INFORMATION</span>
            <h2 id="customer-profile-title-{{ $customer->id }}">{{ $customer->name }}</h2>
        </div>
        <button class="button button-small button-outline" type="button" data-profile-close autofocus>Close</button>
    </div>

    <div class="profile-content">
        <dl class="profile-details">
            <div>
                <dt>Mobile number</dt>
                <dd>{{ $customer->phone ?: 'Not provided' }}</dd>
            </div>
            <div>
                <dt>Email</dt>
                <dd>{{ $customer->email ?: 'Not provided' }}</dd>
            </div>
            <div>
                <dt>Registered</dt>
                <dd>{{ $customer->created_at?->format('M d, Y') ?? 'Not provided' }}</dd>
            </div>
            <div>
                <dt>Account status</dt>
                <dd>{{ $customer->declined_at ? 'Declined' : ($customer->is_approved ? 'Approved' : 'Awaiting approval') }}</dd>
            </div>
            <div class="profile-address">
                <dt>Delivery address</dt>
                <dd>{{ $customer->address ?: 'Not provided' }}</dd>
            </div>
        </dl>

        <div class="profile-metrics customer-order-metrics" aria-label="Customer order summary">
            <div class="profile-metric"><span>Orders</span><strong>{{ $customer->orders_count }}</strong></div>
            <div class="profile-metric"><span>Containers ordered</span><strong>{{ (int) $customer->orders_sum_quantity }}</strong></div>
            <div class="profile-metric">
                <span>Last order</span>
                <strong>{{ $customer->orders_max_created_at ? \Illuminate\Support\Carbon::parse($customer->orders_max_created_at)->format('M d, Y') : 'No orders yet' }}</strong>
            </div>
        </div>
        <p class="profile-note">Totals include the customer’s complete order history, including archived orders.</p>

        <section aria-labelledby="customer-orders-title-{{ $customer->id }}">
            <h3 id="customer-orders-title-{{ $customer->id }}">Orders in this view</h3>
            @foreach ($visibleOrders as $customerOrder)
                <article class="profile-order">
                    <div class="profile-order-heading">
                        <div>
                            <strong>Order #{{ $customerOrder->display_number }}</strong>
                            <p>{{ $customerOrder->quantity }} containers · {{ $customerOrder->container_type_label }}</p>
                        </div>
                        <span class="status-pill status-{{ $customerOrder->status }}">{{ \App\Models\Order::STATUSES[$customerOrder->status] }}</span>
                    </div>
                    <dl class="profile-details profile-order-details">
                        <div>
                            <dt>Placed</dt>
                            <dd>{{ $customerOrder->created_at->format('M d, Y · g:i A') }}</dd>
                        </div>
                        <div>
                            <dt>Delivery address for this order</dt>
                            <dd>{{ $customerOrder->delivery_address }}</dd>
                        </div>
                        @if ($customerOrder->notes)
                            <div class="profile-address">
                                <dt>Delivery note</dt>
                                <dd>{{ $customerOrder->notes }}</dd>
                            </div>
                        @endif
                    </dl>
                </article>
            @endforeach
        </section>
    </div>
</dialog>

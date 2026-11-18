@foreach ($orders->pluck('user')->unique('id') as $customer)
    @include('admin.customers.profile-dialog', ['customer' => $customer, 'visibleOrders' => $orders->where('user_id', $customer->id)])
@endforeach

@extends('layouts.app')

@section('title', 'Costumer Data')

@section('content')
    <div class="page-heading">
        <span class="eyebrow">ADMIN ARCHIVE</span>
        <h1>Costumer Data</h1>
        <p>Delivered orders saved for reference. You can permanently delete archived records here.</p>
    </div>

    <section class="content-section">
        <div class="section-heading">
            <div>
                <span class="eyebrow">ARCHIVED ORDERS</span>
                <h2>{{ $orders->total() }} records</h2>
            </div>
            <a class="text-link" href="{{ route('admin.orders') }}">Back to active orders <span>→</span></a>
        </div>

        @if ($orders->isEmpty())
            <div class="empty-state compact-empty">
                <h3>No archived orders yet.</h3>
                <p>Delivered orders can be moved here from the admin order queue.</p>
            </div>
        @else
            <div class="table-wrap">
                <table class="data-table admin-order-table">
                    <thead>
                        <tr>
                            <th>ORDER</th>
                            <th>CUSTOMER</th>
                            <th>DELIVERY ADDRESS</th>
                            <th>QUANTITY</th>
                            <th>PLACED</th>
                            <th>ARCHIVED</th>
                            <th>ACTION</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($orders as $order)
                            <tr>
                                <td class="order-number">#{{ $order->display_number }}</td>
                                <td>
                                    <strong>{{ $order->user->name }}</strong>
                                    <small class="table-subline">{{ $order->user->email }} · {{ $order->user->phone }}</small>
                                </td>
                                <td class="address-cell" title="{{ $order->delivery_address }}">{{ $order->delivery_address }}</td>
                                <td>{{ $order->quantity }} containers</td>
                                <td>{{ $order->created_at->format('M j, Y') }}</td>
                                <td>{{ $order->archived_at->format('M j, Y') }}</td>
                                <td>
                                    <form method="POST" action="{{ route('admin.archive.delete', $order) }}" onsubmit="return confirm('Permanently delete archived order #{{ $order->display_number }} and its delivery notification? This cannot be undone.')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="delete-button" type="submit">Delete data</button>
                                    </form>
                                </td>
                            </tr>
                            @if ($order->notes)
                                <tr class="order-note-row">
                                    <td></td>
                                    <td colspan="6"><span class="eyebrow">DELIVERY NOTE</span> {{ $order->notes }}</td>
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

@extends('layouts.app')

@section('title', 'Customer feedback')

@section('content')
    <div class="page-heading">
        <span class="eyebrow">CUSTOMER EXPERIENCE</span>
        <h1>Customer feedback</h1>
        <p>Read customer ratings and comments, with the newest submissions first.</p>
    </div>

    <section aria-labelledby="feedback-heading">
        <div class="section-heading">
            <h2 id="feedback-heading">All feedback</h2>
            <span class="section-count">{{ $feedback->total() }} submitted</span>
        </div>

        @if ($feedback->isEmpty())
            <div class="empty-state compact-empty">
                <h3>No customer feedback yet.</h3>
                <p>Customer submissions will appear here.</p>
            </div>
        @else
            <div class="feedback-list">
                @foreach ($feedback as $entry)
                    <article class="feedback-card">
                        <div class="feedback-card-heading">
                            <h3>{{ $entry->user->name }}</h3>
                            <span class="feedback-rating">{{ $entry->rating }} out of 5</span>
                        </div>
                        <dl class="feedback-details">
                            <div>
                                <dt>Mobile number</dt>
                                <dd>{{ $entry->user->phone ?: 'Not provided' }}</dd>
                            </div>
                            @if ($entry->user->email)
                                <div>
                                    <dt>Email</dt>
                                    <dd>{{ $entry->user->email }}</dd>
                                </div>
                            @endif
                            <div>
                                <dt>Submitted</dt>
                                <dd><time datetime="{{ $entry->created_at->toIso8601String() }}">{{ $entry->created_at->format('M j, Y · g:i A') }}</time></dd>
                            </div>
                        </dl>
                        <p class="feedback-message">{{ $entry->message }}</p>
                    </article>
                @endforeach
            </div>
            <div class="pagination-wrap">{{ $feedback->links('layouts.partials.pagination') }}</div>
        @endif
    </section>
@endsection

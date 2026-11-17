@extends('layouts.app')

@section('title', 'Feedback')

@section('content')
    <div class="page-heading">
        <span class="eyebrow">WE’RE LISTENING</span>
        <h1>Share your feedback</h1>
        <p>Tell us about your experience so we can improve our service.</p>
    </div>

    <div class="feedback-layout">
        <form class="form-panel form-stack feedback-form" method="POST" action="{{ route('feedback.store') }}">
            @csrf
            <h2>Rate your experience</h2>
            <p class="feedback-hint">Your feedback is visible to you and the station’s administrators.</p>

            <x-star-rating name="rating" label="Overall experience" :selected="$selectedRating" />
            <x-star-rating name="product_quality_rating" label="Product quality" :selected="$selectedProductQuality" />

            <label>
                Your feedback
                <textarea name="message" rows="5" maxlength="2000" required data-feedback-message aria-describedby="feedback-message-hint" placeholder="How was the water quality and your delivery?" @error('message') aria-invalid="true" @enderror>{{ $message }}</textarea>
                @error('message') <small class="field-error">{{ $message }}</small> @enderror
            </label>
            <p class="feedback-hint feedback-message-hint" id="feedback-message-hint"><span>Share what went well or what we could improve.</span> <span data-feedback-count>{{ mb_strlen($message) }}/2000</span></p>
            <button class="button button-primary" type="submit">Send feedback</button>
        </form>

        <section aria-labelledby="your-feedback-heading">
            <div class="section-heading">
                <h2 id="your-feedback-heading">Your feedback</h2>
                <span class="section-count">{{ $feedback->total() }} submitted</span>
            </div>

            @if ($feedback->isEmpty())
                <div class="empty-state compact-empty">
                    <h3>No feedback yet.</h3>
                    <p>Your submitted feedback will appear here.</p>
                </div>
            @else
                <div class="feedback-list">
                    @foreach ($feedback as $entry)
                        <article class="feedback-card">
                            <div class="feedback-card-heading">
                                <time class="feedback-date" datetime="{{ $entry->created_at->toIso8601String() }}">{{ $entry->created_at->format('M j, Y · g:i A') }}</time>
                            </div>
                            <x-feedback-ratings :rating="$entry->rating" :product-quality-rating="$entry->product_quality_rating" />
                            <p class="feedback-message">{{ $entry->message }}</p>
                        </article>
                    @endforeach
                </div>
                <div class="pagination-wrap">{{ $feedback->links('layouts.partials.pagination') }}</div>
            @endif
        </section>
    </div>
@endsection

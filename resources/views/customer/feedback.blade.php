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
            <h2>How did we do?</h2>
            <p class="feedback-hint">Your feedback is visible to you and the station’s administrators.</p>

            <label>
                Rating
                <select name="rating" required @error('rating') aria-invalid="true" @enderror>
                    <option value="" disabled @selected($selectedRating === '')>Choose a rating</option>
                    @foreach ([1 => '1 - Very poor', 2 => '2 - Poor', 3 => '3 - Okay', 4 => '4 - Good', 5 => '5 - Excellent'] as $rating => $label)
                        <option value="{{ $rating }}" @selected($selectedRating === (string) $rating)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('rating') <small class="field-error">{{ $message }}</small> @enderror
            </label>

            <label>
                Your feedback
                <textarea name="message" rows="6" maxlength="2000" required @error('message') aria-invalid="true" @enderror>{{ $message }}</textarea>
                @error('message') <small class="field-error">{{ $message }}</small> @enderror
            </label>
            <p class="feedback-hint">You can write up to 2,000 characters.</p>
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
                                <span class="feedback-rating">{{ $entry->rating }} out of 5</span>
                                <time class="feedback-date" datetime="{{ $entry->created_at->toIso8601String() }}">{{ $entry->created_at->format('M j, Y · g:i A') }}</time>
                            </div>
                            <p class="feedback-message">{{ $entry->message }}</p>
                        </article>
                    @endforeach
                </div>
                <div class="pagination-wrap">{{ $feedback->links('layouts.partials.pagination') }}</div>
            @endif
        </section>
    </div>
@endsection

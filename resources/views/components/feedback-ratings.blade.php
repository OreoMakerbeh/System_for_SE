@props(['rating', 'productQualityRating'])

<dl class="feedback-scores">
    @foreach (['Overall experience' => $rating, 'Product quality' => $productQualityRating] as $label => $score)
        <div>
            <dt>{{ $label }}</dt>
            <dd>
                <span class="feedback-stars" aria-hidden="true">
                    @foreach (range(1, 5) as $star)
                        <span @class(['is-filled' => $score !== null && $star <= $score])>★</span>
                    @endforeach
                </span>
                <span>{{ $score === null ? 'Not rated' : $score.' out of 5' }}</span>
            </dd>
        </div>
    @endforeach
</dl>

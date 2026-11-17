<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FeedbackController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->role === 'customer', 403);

        $rating = $request->old('rating');
        $productQuality = $request->old('product_quality_rating');
        $message = $request->old('message');

        return view('customer.feedback', [
            'feedback' => $request->user()->feedback()->latest()->orderByDesc('id')->paginate(6),
            'selectedRating' => is_scalar($rating) ? (string) $rating : '',
            'selectedProductQuality' => is_scalar($productQuality) ? (string) $productQuality : '',
            'message' => is_string($message) ? $message : '',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->role === 'customer', 403);

        $details = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'product_quality_rating' => ['required', 'integer', 'between:1,5'],
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $request->user()->feedback()->create($details);

        return redirect()->route('feedback.index')
            ->with('success', 'Thank you. Your feedback has been sent to the station.');
    }
}

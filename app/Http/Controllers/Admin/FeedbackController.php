<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use Illuminate\View\View;

class FeedbackController extends Controller
{
    public function index(): View
    {
        return view('admin.feedback', [
            'feedback' => Feedback::with('user')->latest()->orderByDesc('id')->paginate(12),
        ]);
    }
}

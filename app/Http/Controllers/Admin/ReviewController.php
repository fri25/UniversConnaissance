<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReviewController extends Controller
{
    public function index(Request $request): View
    {
        $reviews = Review::with(['user:id,name,email', 'book:id,title,slug'])
            ->when($request->query('status') === 'hidden', fn ($q) => $q->where('is_approved', false))
            ->when($request->query('status') === 'visible', fn ($q) => $q->where('is_approved', true))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.reviews.index', compact('reviews'));
    }

    public function toggle(Review $review): RedirectResponse
    {
        $review->update(['is_approved' => ! $review->is_approved]);

        return back()->with('status', $review->is_approved ? 'Avis publié.' : 'Avis masqué.');
    }

    public function destroy(Review $review): RedirectResponse
    {
        $review->delete();

        return back()->with('status', 'Avis supprimé.');
    }
}

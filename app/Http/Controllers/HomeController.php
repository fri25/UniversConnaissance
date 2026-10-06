<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Category;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $newReleases = Book::active()->forCard()->latest()->take(8)->get();

        $bestSellers = Book::active()->forCard()
            ->withCount('paidOrders as sales_count')
            ->orderByDesc('sales_count')
            ->orderByDesc('is_featured')
            ->take(8)
            ->get();

        $catalogue = Book::active()->forCard()
            ->orderByDesc('is_featured')
            ->orderBy('title')
            ->take(12)
            ->get();

        $categories = Category::whereNull('parent_id')
            ->withCount(['books' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('name')
            ->get();

        return view('home', compact('newReleases', 'bestSellers', 'catalogue', 'categories'));
    }
}

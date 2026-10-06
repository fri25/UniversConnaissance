<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Order;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $paid = Order::paid();

        $stats = [
            'revenue' => (int) (clone $paid)->sum('amount'),
            'revenue_30' => (int) (clone $paid)->where('paid_at', '>=', now()->subDays(30))->sum('amount'),
            'sales' => (clone $paid)->count(),
            'pending' => Order::where('status', Order::STATUS_PENDING)->count(),
            'customers' => User::where('is_admin', false)->count(),
            'books' => Book::where('is_active', true)->count(),
            'hidden_reviews' => Review::where('is_approved', false)->count(),
        ];

        $topBooks = Book::withCount('paidOrders as sales_count')
            ->withSum('paidOrders as revenue', 'amount')
            ->whereHas('paidOrders')
            ->orderByDesc('sales_count')
            ->take(5)
            ->get();

        $topCategories = DB::table('orders')
            ->join('book_category', 'book_category.book_id', '=', 'orders.book_id')
            ->join('categories', 'categories.id', '=', 'book_category.category_id')
            ->where('orders.status', Order::STATUS_PAID)
            ->groupBy('categories.id', 'categories.name')
            ->select('categories.name', DB::raw('count(*) as sales_count'), DB::raw('sum(orders.amount) as revenue'))
            ->orderByDesc('sales_count')
            ->take(5)
            ->get();

        // Chiffre d'affaires quotidien sur 30 jours (jours sans vente inclus).
        $daily = (clone $paid)->where('paid_at', '>=', now()->subDays(29)->startOfDay())
            ->get(['amount', 'paid_at'])
            ->groupBy(fn (Order $o) => $o->paid_at->toDateString())
            ->map->sum('amount');

        $chart = collect(range(29, 0))->map(function (int $daysAgo) use ($daily) {
            $date = Carbon::today()->subDays($daysAgo);

            return ['date' => $date, 'amount' => (int) ($daily[$date->toDateString()] ?? 0)];
        });

        $latestOrders = Order::with(['user:id,name', 'book:id,title'])->latest()->take(8)->get();

        return view('admin.dashboard', compact('stats', 'topBooks', 'topCategories', 'chart', 'latestOrders'));
    }
}

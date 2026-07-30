<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function getStats()
    {
        $today = Carbon::today();

        // --- 1. RINGKASAN HARI INI (Hanya yang SUKSES) ---
        $todaysTransactions = Transaction::whereDate('created_at', $today)
            ->where('status', 'success') // <--- TAMBAHAN PENTING
            ->count();

        $todaysRevenue = Transaction::whereDate('created_at', $today)
            ->where('status', 'success') // <--- TAMBAHAN PENTING
            ->sum('final_amount');

        $todaysMemberTransactions = Transaction::whereDate('created_at', $today)
            ->where('status', 'success') // <--- TAMBAHAN PENTING
            ->whereNotNull('member_id')
            ->count();

        $todaysMemberRevenue = Transaction::whereDate('created_at', $today)
            ->where('status', 'success') // <--- TAMBAHAN PENTING
            ->whereNotNull('member_id')
            ->sum('final_amount');
    
        // --- 2. GRAFIK 7 HARI (Hanya yang SUKSES) ---
        $salesLast7Days = Transaction::where('created_at', '>=', Carbon::now()->subDays(6)->startOfDay())
            ->where('status', 'success') // <--- TAMBAHAN PENTING
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(CASE WHEN member_id IS NOT NULL THEN final_amount ELSE 0 END) as member_total'),
                DB::raw('SUM(CASE WHEN member_id IS NULL THEN final_amount ELSE 0 END) as non_member_total')
            )
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date', 'ASC')
            ->get();
            
        // --- 3. PRODUK TERLARIS (Hanya dari Transaksi SUKSES) ---
        // Kita gunakan whereHas untuk mengecek status di tabel parent (transactions)
        $topSellingProducts = TransactionItem::whereHas('transaction', function($q) {
                $q->where('status', 'success'); // <--- TAMBAHAN PENTING
            })
            ->select('product_id', DB::raw('SUM(quantity) as total_sold'))
            ->with('product:id,name,sku')
            ->groupBy('product_id')
            ->orderBy('total_sold', 'desc')
            ->limit(5)
            ->get();
    
        // --- 4. TOP MEMBER (Hanya Transaksi SUKSES) ---
        $topMembers = Transaction::select('member_id', DB::raw('SUM(final_amount) as total_spent'))
            ->where('status', 'success') // <--- TAMBAHAN PENTING
            ->with('member:id,name')
            ->whereNotNull('member_id')
            ->groupBy('member_id')
            ->orderBy('total_spent', 'desc')
            ->limit(5)
            ->get();
    
        return response()->json([
            'todays_transactions' => $todaysTransactions,
            'todays_revenue' => (float) $todaysRevenue,
            'todays_member_transactions' => $todaysMemberTransactions,
            'todays_member_revenue' => (float) $todaysMemberRevenue,
            'sales_last_7_days' => $salesLast7Days,
            'top_selling_products' => $topSellingProducts,
            'top_members' => $topMembers,
        ]);
    }

    public function getReports(Request $request)
    {
        $range = $request->input('range', 'week');
        $endDate = Carbon::now()->endOfDay();

        switch ($range) {
            case 'month':
                $startDate = Carbon::now()->subMonth()->startOfDay();
                break;
            case 'year':
                $startDate = Carbon::now()->subYear()->startOfDay();
                break;
            case 'week':
            default:
                $startDate = Carbon::now()->subDays(6)->startOfDay();
                break;
        }

        // --- 5. GRAFIK LAPORAN (Hanya yang SUKSES) ---
        $salesOverTime = Transaction::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'success') // <--- TAMBAHAN PENTING
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(CASE WHEN member_id IS NOT NULL THEN final_amount ELSE 0 END) as member_total'),
                DB::raw('SUM(CASE WHEN member_id IS NULL THEN final_amount ELSE 0 END) as non_member_total')
            )
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date', 'asc')
            ->get();
        
        $formattedSales = $salesOverTime->map(function ($item) {
            return [
                'date' => Carbon::parse($item->date)->format('d M'),
                'member_total' => (float) $item->member_total,
                'non_member_total' => (float) $item->non_member_total,
            ];
        });

        // --- 6. KATEGORI (Hanya yang SUKSES) ---
        $salesByCategory = DB::table('transaction_items')
            ->join('products', 'transaction_items.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->join('transactions', 'transaction_items.transaction_id', '=', 'transactions.id')
            ->whereBetween('transactions.created_at', [$startDate, $endDate])
            ->where('transactions.status', 'success') // <--- TAMBAHAN PENTING (Untuk Query Builder)
            ->select('categories.name', DB::raw('SUM(transaction_items.price * transaction_items.quantity) as total_revenue'))
            ->groupBy('categories.name')
            ->orderByDesc('total_revenue')
            ->get();

        return response()->json([
            'sales_over_time' => $formattedSales,
            'sales_by_category' => $salesByCategory,
        ]);
    }
}
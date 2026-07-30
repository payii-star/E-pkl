<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductVariant; // DIUBAH: Menggunakan model ProductVariant
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class StockController extends Controller
{
    /**
     * Menambahkan atau menyesuaikan stok untuk sebuah varian.
     */
    public function adjustStock(Request $request)
    {
        $request->validate([
            'variant_id'      => 'required|exists:product_variants,id', // DIUBAH: Nama tabel disesuaikan
            'quantity_change' => 'required|integer|not_in:0',
            'type'            => 'required|in:stock_in,adjustment,reversal',
            'notes'           => 'nullable|string',
        ]);

        $variant = ProductVariant::findOrFail($request->variant_id);
        $quantityChange = $request->quantity_change;

        // Update stok pada tabel variant
        $variant->stock += $quantityChange;
        $variant->save();

        // Catat pergerakan stok
        StockMovement::create([
            'product_variant_id' => $variant->id,
            'user_id'         => Auth::id(),
            'quantity_change' => $quantityChange,
            'type'            => $request->type,
            'notes'           => $request->notes ?? "Penyesuaian stok oleh admin.",
        ]);

        return response()->json([
            'message' => 'Stock successfully adjusted.',
            'data'    => $variant,
        ]);
    }

    /**
     * Menampilkan riwayat stok untuk satu varian spesifik.
     */
    public function history(ProductVariant $variant) // DIUBAH: Menggunakan ProductVariant
    {
        $movements = StockMovement::where('product_variant_id', $variant->id)
            ->with('user:id,name')
            ->latest()
            ->paginate(10);

        return response()->json($movements);
    }


    public function fullHistory(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
        ]);
    
        // Query yang benar, sama seperti di exportHistory, tapi menggunakan paginate()
        $query = StockMovement::with([
            'productVariant:id,product_id,sku,options',
            'productVariant.product:id,name',
            'user:id,name'
        ])->latest();
    
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }
    
        $history = $query->paginate(20); // Menggunakan paginate untuk ditampilkan di halaman
    
        return response()->json($history);
    }

    /**
     * Mengekspor riwayat pergerakan stok ke file CSV.
     */
    public function exportHistory(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
        ]);

        $fileName = 'stock_movement_report_' . Carbon::now()->format('Ymd_His') . '.csv';
        
        $query = StockMovement::with([
            'productVariant:id,product_id,sku,options',
            'productVariant.product:id,name',
            'user:id,name'
        ])->latest();

        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }
        
        $movements = $query->get();

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];
        
        $formatVariant = function ($options) {
            if (!$options || !is_object($options)) return '';
            return implode(' / ', (array) $options);
        };

        return response()->stream(function () use ($movements, $formatVariant) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Date', 'Product', 'Variant', 'SKU', 'User', 'Type', 'Change', 'Notes']);

            foreach ($movements as $movement) {
                fputcsv($file, [
                    $movement->created_at->format('Y-m-d H:i:s'),
                    $movement->productVariant->product->name ?? 'N/A',
                    $formatVariant($movement->productVariant->options ?? null),
                    $movement->productVariant->sku ?? 'N/A',
                    $movement->user->name ?? 'System',
                    $movement->type,
                    $movement->quantity_change,
                    $movement->notes
                ]);
            }
            fclose($file);
        }, 200, $headers);
    }
}
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\ProductVariant;
use App\Models\Promo;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\PointSetting;
use App\Models\StockMovement;
use App\Models\PointMovement; // <--- WAJIB DI-IMPORT
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Midtrans\Config;
use Midtrans\Snap;

class TransactionController extends Controller
{
    public function show($invoice_number)
    {
        // Cari transaksi berdasarkan kolom 'invoice_number'
        $transaction = Transaction::with(['items.product', 'user:id,name', 'member:id,name'])
            ->where('invoice_number', $invoice_number)
            ->first();

        // Cek jika tidak ketemu
        if (!$transaction) {
            return response()->json(['message' => 'Transaksi tidak ditemukan'], 404);
        }

        return response()->json($transaction);
    }

    public function getHistory(Request $request)
    {
        $transactions = Transaction::with(['user:id,name', 'member:id,name'])
            ->latest()
            ->paginate(15);
        return response()->json($transactions);
    }

    public function store(Request $request)
    {
        $request->validate([
            'cart' => 'required|array|min:1',
            'cart.*.id' => 'required|exists:product_variants,id',
            'cart.*.quantity' => 'required|integer|min:1',
            'paid_amount' => 'required|numeric|min:0',
            'promo_code' => 'nullable|string|exists:promos,code',
            'member_id' => 'nullable|integer|exists:members,id',
            'points_redeemed' => 'nullable|integer|min:0',
        ]);

        DB::beginTransaction();

        try {
            // 1. Hitung Total Awal & Cek Stok
            $cart = $request->cart;
            $originalAmount = 0;
            
            foreach ($cart as $item) {
                $variant = ProductVariant::lockForUpdate()->find($item['id']);
                if (!$variant || ($variant->stock - $variant->reserved_stock) < $item['quantity']) {
                    throw new \Exception('Stok tersedia untuk varian ' . ($variant->sku ?? $item['id']) . ' tidak cukup.');
                }
                $originalAmount += $variant->price * $item['quantity'];
            }
            
            // 2. Hitung Diskon Promo
            $promo = null;
            $discountAmount = 0;
            
            if ($request->promo_code) {
                $promo = Promo::where('code', $request->promo_code)->first();
                $isValid = $promo && $promo->is_active &&
                            ($promo->start_date == null || $promo->start_date <= now()) &&
                            ($promo->end_date == null || $promo->end_date >= now()) &&
                            ($promo->min_purchase <= $originalAmount);

                if ($promo && $promo->is_member_only && !$request->member_id) {
                    throw new \Exception('Promo ini hanya berlaku untuk member.');
                }

                if ($isValid) {
                    if ($promo->type == 'percentage') {
                        $discountAmount = $originalAmount * ($promo->value / 100);
                    } else {
                        $discountAmount = $promo->value;
                    }
                } else {
                    throw new \Exception('Kode promo tidak valid atau syarat tidak terpenuhi.');
                }
            }

            $amountAfterPromo = $originalAmount - $discountAmount;

            // 3. Logika Penukaran Poin (Redeem)
            $pointsRedeemed = 0;
            $pointDiscountAmount = 0;

            if ($request->member_id && $request->points_redeemed > 0) {
                $member = Member::lockForUpdate()->find($request->member_id); 

                if ($member->points < $request->points_redeemed) {
                    throw new \Exception('Poin member tidak mencukupi.');
                }

                $redemptionRate = PointSetting::where('key', 'point_redemption_value')->value('value') ?? 0;
                $pointDiscountAmount = $request->points_redeemed * $redemptionRate;

                if ($pointDiscountAmount > $amountAfterPromo) {
                    throw new \Exception('Nilai tukar poin melebihi total tagihan.');
                }

                $pointsRedeemed = $request->points_redeemed;
                
                // Potong saldo poin member
                $member->decrement('points', $pointsRedeemed);
            }

            // 4. Hitung Final Amount
            $finalAmount = max(0, $amountAfterPromo - $pointDiscountAmount);

            // 5. Hitung Potensi Poin Didapat
            $pointsEarned = 0;
            if ($request->member_id && $finalAmount > 0) {
                $earningRate = PointSetting::where('key', 'rupiah_per_point')->value('value') ?? 10000;
                $pointsEarned = floor($finalAmount / $earningRate);
            }

            // 6. Simpan Transaksi
            $transaction = Transaction::create([
                'invoice_number' => 'INV-' . time() . Str::random(5),
                'user_id' => auth()->id(),
                'member_id' => $request->member_id,
                'original_amount' => $originalAmount,
                'discount_amount' => $discountAmount,
                'points_redeemed' => $pointsRedeemed,
                'point_discount_amount' => $pointDiscountAmount,
                'points_earned' => $pointsEarned,
                'final_amount' => $finalAmount,
                'paid_amount' => $request->paid_amount,
                'change_amount' => $request->paid_amount - $finalAmount,
                'payment_method' => $request->payment_method,
                'promo_code' => $promo ? $promo->code : null,
                'promo_id' => $promo ? $promo->id : null,
                'status' => ($request->payment_method == 'cash') ? 'success' : 'pending',
            ]);

            // --- PERBAIKAN 1: CATAT HISTORY REDEEM POIN ---
            if ($request->member_id && $pointsRedeemed > 0) {
                PointMovement::create([
                    'member_id'      => $request->member_id,
                    'transaction_id' => $transaction->id,
                    'points_change'  => -$pointsRedeemed, // Negatif karena dipakai
                    'description'    => 'Penukaran Poin Transaksi #' . $transaction->invoice_number,
                ]);
            }

            // 7. Simpan Item & Update Stok
            foreach ($cart as $item) {
                $variant = ProductVariant::find($item['id']);
                
                TransactionItem::create([
                    'transaction_id' => $transaction->id,
                    'product_id' => $variant->product_id,
                    'product_variant_id' => $variant->id, 
                    'quantity' => $item['quantity'],
                    'price' => $variant->price,
                ]);
                
                if ($request->payment_method == 'cash') {
                    $variant->decrement('stock', $item['quantity']);
                    StockMovement::create([
                        'product_variant_id' => $variant->id,
                        'user_id' => auth()->id(),
                        'quantity_change' => -$item['quantity'],
                        'type' => 'sale',
                        'notes' => 'Penjualan Tunai ' . $transaction->invoice_number,
                    ]);
                } else {
                    $variant->increment('reserved_stock', $item['quantity']);
                }
            }

            // 8. Jika CASH, berikan poin sekarang (dan catat history)
            if ($request->payment_method == 'cash' && $pointsEarned > 0 && $request->member_id) {
                $member = Member::find($request->member_id);
                $member->increment('points', $pointsEarned);
                
                // --- PERBAIKAN 2: CATAT HISTORY POIN MASUK (CASH) ---
                PointMovement::create([
                    'member_id'      => $request->member_id,
                    'transaction_id' => $transaction->id,
                    'points_change'  => $pointsEarned, // Positif
                    'description'    => 'Reward Poin Transaksi Tunai #' . $transaction->invoice_number,
                ]);
            }

            // 9. Integrasi Midtrans (Jika bukan Tunai)
            $paymentUrl = null;
            if ($request->payment_method != 'cash') {
                Config::$serverKey = env('MIDTRANS_SERVER_KEY');
                Config::$isProduction = filter_var(env('MIDTRANS_IS_PRODUCTION', false), FILTER_VALIDATE_BOOLEAN);
                Config::$isSanitized = true;
                Config::$is3ds = true;

                if (empty(Config::$serverKey)) {
                    throw new \Exception('Midtrans Server Key belum dikonfigurasi.');
                }

                $params = [
                    'transaction_details' => [
                        'order_id' => $transaction->invoice_number,
                        'gross_amount' => (int) $transaction->final_amount,
                    ],
                    'customer_details' => [
                        'first_name' => $request->member_id ? 'Member' : 'Guest',
                        'last_name' => $request->member_id ? (string)$request->member_id : '',
                        'email' => 'customer@example.com', 
                    ],
                    'expiry' => [
                        'start_time' => date("Y-m-d H:i:s O"),
                        'unit' => 'minute',
                        'duration' => 15  
                    ],
                ];

                $paymentUrl = Snap::createTransaction($params)->redirect_url;
                $transaction->snap_token = $paymentUrl;
                $transaction->save();
            }

            DB::commit();

            $transaction->load('member');

            return response()->json([
                'message' => 'Transaction created!',
                'data' => $transaction,
                'payment_url' => $paymentUrl 
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Transaction failed: ' . $e->getMessage());
            return response()->json(['message' => $e->getMessage()], 422); 
        }
    }

    public function cancel($invoice_number)
    {
        $transaction = Transaction::with('items.productVariant')
            ->where('invoice_number', $invoice_number)
            ->first();

        if (!$transaction) {
            return response()->json(['message' => 'Transaksi tidak ditemukan'], 404);
        }

        if ($transaction->status !== 'pending') {
            return response()->json(['message' => 'Status transaksi sudah final, tidak bisa dibatalkan.'], 400);
        }

        DB::beginTransaction();
        try {
            $transaction->status = 'failed';
            $transaction->save();

            // Refund Poin (Jika ada)
            if ($transaction->member_id && $transaction->points_redeemed > 0) {
                $member = Member::find($transaction->member_id);
                if ($member) {
                    $member->increment('points', $transaction->points_redeemed);

                    // --- PERBAIKAN 3: CATAT HISTORY REFUND POIN ---
                    PointMovement::create([
                        'member_id'      => $member->id,
                        'transaction_id' => $transaction->id,
                        'points_change'  => $transaction->points_redeemed, // Positif karena dikembalikan
                        'description'    => 'Pengembalian Poin (Batal) #' . $transaction->invoice_number,
                    ]);
                }
            }

            // Release Stock
            foreach ($transaction->items as $item) {
                $variant = $item->productVariant;
                if ($variant) {
                    if ($variant->reserved_stock >= $item->quantity) {
                        $variant->decrement('reserved_stock', $item->quantity);
                    } else {
                        $variant->update(['reserved_stock' => 0]);
                    }
                }
            }

            DB::commit();
            return response()->json(['message' => 'Pesanan dibatalkan & stok dikembalikan.']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal membatalkan.'], 500);
        }
    }
}
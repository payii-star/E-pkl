<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductVariant;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Midtrans\Config;
use Midtrans\Snap;
use App\Models\Promo;
use App\Models\Member;
use App\Models\PointMovement; // TAMBAHKAN: Import model PointMovement

class MidtransController extends Controller
{
    public function __construct()
    {
        Config::$serverKey = config('midtrans.server_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized = config('midtrans.is_sanitized');
        Config::$is3ds = config('midtrans.is_3ds');
    }

    public function createTransaction(Request $request)
    {
        $validatedData = $request->validate([
            'cart' => 'required|array|min:1',
            'cart.*.id' => 'required|exists:product_variants,id',
            'cart.*.quantity' => 'required|integer|min:1',
            'promo_code' => 'nullable|string|exists:promos,code',
            'member_id' => 'nullable|integer|exists:members,id',
            'redeemed_points' => 'nullable|integer|min:0',
        ]);
        
        DB::beginTransaction();
        try {
            $cartItems = $validatedData['cart'];
            $originalAmount = 0;
            $item_details_midtrans = [];
            $transaction_items_db = [];
    
            $variantIds = collect($cartItems)->pluck('id');
            $variants = ProductVariant::with('product')->find($variantIds)->keyBy('id');
    
            foreach ($cartItems as $item) {
                $variant = $variants[$item['id']] ?? null;
    
                if (!$variant || $variant->available_stock < $item['quantity']) {
                    throw new \Exception('Stock untuk produk ' . ($variant->product->name ?? '') . ' tidak cukup.');
                }
    
                $originalAmount += $variant->price * $item['quantity'];
    
                $item_details_midtrans[] = [
                    'id'       => $variant->id,
                    'price'    => (int) $variant->price,
                    'quantity' => (int) $item['quantity'],
                    'name'     => $variant->product->name . ' (' . implode(' / ', (array)$variant->options) . ')',
                ];
    
                $transaction_items_db[] = [
                    'product_variant_id' => $variant->id,
                    'product_id'         => $variant->product_id,
                    'quantity'           => $item['quantity'],
                    'price'              => $variant->price,
                    'created_at'         => now(),
                    'updated_at'         => now(),
                ];
            }
            
            $promo = null;
            $promoDiscountAmount = 0;
            $finalAmount = $originalAmount;
    
            if ($request->promo_code) {
                $promo = Promo::where('code', $request->promo_code)->first();
                if ($promo && $promo->is_member_only && !$request->member_id) {
                    throw new \Exception('Promo ini hanya berlaku untuk member.');
                }
                $isValid = $promo && $promo->is_active && ($promo->start_date == null || $promo->start_date <= now()) && ($promo->end_date == null || $promo->end_date >= now()) && ($promo->min_purchase <= $originalAmount);
                if ($isValid) {
                    if ($promo->type == 'percentage') {
                        $promoDiscountAmount = $originalAmount * ($promo->value / 100);
                    } else {
                        $promoDiscountAmount = $promo->value;
                    }
                    $finalAmount = $originalAmount - $promoDiscountAmount;
                } else {
                    if ($promo) { throw new \Exception('Kode promo tidak valid atau tidak memenuhi syarat.'); }
                }
            }
            
            $pointDiscountAmount = 0;
            $pointsRedeemed = $validatedData['redeemed_points'] ?? 0;
            $member = null;

            if ($request->member_id && $pointsRedeemed > 0) {
                $member = Member::find($request->member_id);

                if (!$member || $member->points < $pointsRedeemed) {
                    throw new \Exception('Poin member tidak mencukupi untuk ditukar.');
                }

                $pointValueInRupiah = 100; // Bisa diambil dari config atau database
                $pointDiscountAmount = $pointsRedeemed * $pointValueInRupiah;

                if ($pointDiscountAmount > $finalAmount) {
                    throw new \Exception('Nilai penukaran poin melebihi total belanja.');
                }

                $finalAmount -= $pointDiscountAmount;
            }

            foreach ($cartItems as $item) {
                $variants[$item['id']]->increment('reserved_stock', $item['quantity']);
            }

            $invoiceNumber = 'POS-MID-' . time();
            $transaction = Transaction::create([
                'invoice_number'        => $invoiceNumber,
                'user_id'               => auth()->id(),
                'member_id'             => $validatedData['member_id'] ?? null,
                'original_amount'       => $originalAmount,
                'discount_amount'       => $promoDiscountAmount,
                'points_redeemed'       => $pointsRedeemed,
                'point_discount_amount' => $pointDiscountAmount,
                'final_amount'          => $finalAmount,
                'status'                => 'pending',
                'paid_amount'           => 0,
                'change_amount'         => 0,
                'payment_method'        => 'midtrans',
                'promo_code'            => $promo ? $promo->code : null,
                'promo_id'              => $promo ? $promo->id : null,
            ]);

            // Jika ada penukaran poin, kurangi poin dan catat historynya
            if ($member && $pointsRedeemed > 0) {
                $member->decrement('points', $pointsRedeemed);
                PointMovement::create([
                    'member_id' => $member->id,
                    'transaction_id' => $transaction->id,
                    'points_change' => -$pointsRedeemed,
                    'description' => 'Penukaran poin pada transaksi ' . $invoiceNumber,
                ]);
            }
            
            $itemsToInsert = collect($transaction_items_db)->map(function ($item) use ($transaction) {
                $item['transaction_id'] = $transaction->id;
                return $item;
            })->toArray();
    
            TransactionItem::insert($itemsToInsert);
    
            if ($promoDiscountAmount > 0) {
                $item_details_midtrans[] = [
                    'id'       => 'DISCOUNT_PROMO',
                    'price'    => -(int)$promoDiscountAmount,
                    'quantity' => 1,
                    'name'     => 'Discount (' . ($promo->code ?? '') . ')',
                ];
            }
            if ($pointDiscountAmount > 0) {
                $item_details_midtrans[] = [
                    'id'       => 'DISCOUNT_POIN',
                    'price'    => -(int)$pointDiscountAmount,
                    'quantity' => 1,
                    'name'     => 'Redeem Points (' . $pointsRedeemed . ' pts)',
                ];
            }
    
            $params = [
                'transaction_details' => ['order_id' => $invoiceNumber, 'gross_amount' => (int) $finalAmount],
                'item_details'        => $item_details_midtrans,
                'customer_details'    => ['first_name' => auth()->user()->name, 'email' => auth()->user()->email],
            ];
    
            $snapToken = Snap::getSnapToken($params);
            $transaction->update(['snap_token' => $snapToken]);
            
            DB::commit();
    
            return response()->json(['snap_token' => $snapToken]);
    
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }
}
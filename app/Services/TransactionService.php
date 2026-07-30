<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\Member;
use App\Models\StockMovement;
use App\Models\PointMovement; // <--- WAJIB DI-IMPORT
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TransactionService
{
    /**
     * Handle Transaksi SUKSES
     */
    public function handleSuccess(Transaction $transaction)
    {
        // 1. Berikan Poin (Earning)
        if ($transaction->member_id && $transaction->points_earned > 0) {
            $member = Member::find($transaction->member_id);
            if ($member) {
                $member->increment('points', $transaction->points_earned);

                // --- PERBAIKAN: CATAT HISTORY POIN MASUK (ONLINE) ---
                PointMovement::create([
                    'member_id'      => $member->id,
                    'transaction_id' => $transaction->id,
                    'points_change'  => $transaction->points_earned,
                    'description'    => 'Reward Poin Transaksi Online #' . $transaction->invoice_number,
                ]);
            }
        }

        // 2. Finalisasi Stok
        foreach ($transaction->items as $item) {
            $variant = $item->productVariant;
            if ($variant) {
                $newReserved = max(0, $variant->reserved_stock - $item->quantity);
                
                $variant->update([
                    'reserved_stock' => $newReserved,
                    'stock' => $variant->stock - $item->quantity 
                ]);

                StockMovement::create([
                    'product_variant_id' => $variant->id,
                    'user_id' => $transaction->user_id ?? null,
                    'quantity_change' => -$item->quantity,
                    'type' => 'sale',
                    'notes' => 'Online Order ' . $transaction->invoice_number,
                ]);
            }
        }
        
        Log::info("Transaction {$transaction->invoice_number} SUCCESS. Stock finalized.");
    }

    /**
     * Handle Transaksi GAGAL / EXPIRED
     */
    public function handleFailure(Transaction $transaction)
    {
        // 1. Cek Status Awal
        if (in_array($transaction->status, ['failed', 'expired', 'cancel'])) {
            return;
        }

        DB::beginTransaction();
        try {
            $transaction->status = 'failed'; 
            $transaction->save(); 

            // 2. Refund Poin (Jika redeem)
            if ($transaction->member_id && $transaction->points_redeemed > 0) {
                $member = Member::find($transaction->member_id);
                if ($member) {
                    $member->increment('points', $transaction->points_redeemed);

                    // --- PERBAIKAN: CATAT HISTORY REFUND POIN ---
                    PointMovement::create([
                        'member_id'      => $member->id,
                        'transaction_id' => $transaction->id,
                        'points_change'  => $transaction->points_redeemed,
                        'description'    => 'Pengembalian Poin (Expired/Gagal) #' . $transaction->invoice_number,
                    ]);
                }
            }

            // 3. Release Stok
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

            Log::info("Transaction {$transaction->invoice_number} marked as FAILED. Stock released.");
            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Gagal membatalkan transaksi: " . $e->getMessage());
        }
    }
}
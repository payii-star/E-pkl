<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Services\TransactionService; // Pastikan Service di-import
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Midtrans\Config;
use Midtrans\Notification;

class WebhookController extends Controller
{
    // Inject Service
    protected $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    public function handler(Request $request)
    {
        Log::info('--- Midtrans Webhook Received ---', $request->all());

        // 1. Konfigurasi Midtrans
        Config::$serverKey = env('MIDTRANS_SERVER_KEY');
        Config::$isProduction = filter_var(env('MIDTRANS_IS_PRODUCTION', false), FILTER_VALIDATE_BOOLEAN);
        Config::$isSanitized = true;
        Config::$is3ds = true;

        try {
            $notification = new Notification();
        } catch (\Exception $e) {
            Log::error('Midtrans Error: ' . $e->getMessage());
            return response()->json(['error' => 'Invalid signature'], 403);
        }

        // 2. Ambil Data Notifikasi
        $status = $notification->transaction_status;
        $type = $notification->payment_type;
        $orderId = $notification->order_id;
        $fraud = $notification->fraud_status;

        // 3. Cari Transaksi
        // Kita gunakan Eager Loading 'items.productVariant' agar query di service efisien
        $transaction = Transaction::with('items.productVariant')->where('invoice_number', $orderId)->first();

        if (!$transaction) {
            return response()->json(['error' => 'Transaction not found'], 404);
        }

        // 4. Cek apakah sudah diproses sebelumnya (Idempotency)
        // Jika status di DB sudah final (success/failed/expired), abaikan webhook ini
        if (in_array($transaction->status, ['success', 'failed', 'expired'])) {
            return response()->json(['message' => 'Transaction already processed']);
        }

        DB::beginTransaction();
        try {
            $previousStatus = $transaction->status;
            $newStatus = $previousStatus;

            // 5. Tentukan Status Baru berdasarkan Respon Midtrans
            if ($status == 'capture') {
                if ($fraud == 'challenge') {
                    $newStatus = 'challenge';
                } else {
                    $newStatus = 'success';
                }
            } else if ($status == 'settlement') {
                $newStatus = 'success';
            } else if ($status == 'cancel' || $status == 'deny' || $status == 'expire') {
                $newStatus = 'failed';
            } else if ($status == 'pending') {
                $newStatus = 'pending';
            }

            // 6. Simpan Status Baru
            $transaction->status = $newStatus;
            $transaction->save();

            // 7. Panggil Logic Service (Poin & Stok)
            // Hanya jalankan jika status BERUBAH dari Pending ke Final
            
            if ($newStatus == 'success' && $previousStatus != 'success') {
                // Jika sukses: Pindahkan Reserved -> Sold, Beri Poin
                $this->transactionService->handleSuccess($transaction);
            } 
            else if ($newStatus == 'failed' && $previousStatus != 'failed') {
                // Jika gagal: Hapus Reserved (Available naik kembali), Refund Poin
                $this->transactionService->handleFailure($transaction);
            }

            DB::commit();

            return response()->json(['status' => 'ok']);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Webhook Processing Failed for {$orderId}: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Transaction;
use App\Services\TransactionService;
use Carbon\Carbon;

class CheckExpiredTransactions extends Command
{
    // Nama command yang akan dipanggil scheduler
    protected $signature = 'transaction:check-expired';
    protected $description = 'Membatalkan transaksi pending yang sudah kadaluarsa dan mengembalikan stok';

    public function handle(TransactionService $transactionService)
    {
        // Cari transaksi PENDING yang dibuat LEBIH DARI 5 menit yang lalu
        // Kita beri buffer waktu (5 menit) agar tidak bentrok jika Midtrans sedang memproses pembayaran
        $expiredTime = Carbon::now()->subMinutes(5);

        $staleTransactions = Transaction::where('status', 'pending')
            ->where('payment_method', '!=', 'cash') // Cash tidak perlu dicek
            ->where('created_at', '<', $expiredTime)
            ->get();

        if ($staleTransactions->count() > 0) {
            $this->info("Ditemukan {$staleTransactions->count()} transaksi nyangkut. Membersihkan...");
            
            foreach ($staleTransactions as $transaction) {
                $this->info("Membatalkan invoice: " . $transaction->invoice_number);
                $transactionService->handleFailure($transaction);
            }
        } else {
            $this->info('Tidak ada transaksi expired.');
        }
    }
}
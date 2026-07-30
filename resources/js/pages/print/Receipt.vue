<template>
    <div class="receipt-container">
        <div class="receipt-header text-center">
            <h2>Toko Uniqlo KW</h2>
            <p>Jl. Seada-adanya No. 00, Surabaya</p>
            <p>Telp: (021) 123-4567</p>
        </div>
        <hr class="receipt-hr" />
        <div class="receipt-details">
            <div><strong>Invoice #:</strong> {{ transaction.invoice_number }}</div>
            <div><strong>Tanggal:</strong> {{ new Date(transaction.created_at).toLocaleString('id-ID') }}</div>
            <div><strong>Kasir:</strong> {{ transaction.user ? transaction.user.name : 'N/A' }}</div>
            <div v-if="transaction.member"><strong>Member:</strong> {{ transaction.member.name }}</div>
        </div>
        <hr class="receipt-hr" />
        <table class="receipt-table">
            <thead>
            <tr>
                <th>Item</th>
                <th class="text-center">Jml</th>
                <th class="text-end">Harga</th>
                <th class="text-end">Subtotal</th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="item in transaction.items" :key="item.id">
                <td>{{ item.product.name }}</td>
                <td class="text-center">{{ item.quantity }}</td>
                <td class="text-end">Rp{{ formatNumber(item.price) }}</td>
                <td class="text-end">Rp{{ formatNumber(item.price * item.quantity) }}</td>
            </tr>
            </tbody>
        </table>
        <hr class="receipt-hr" />

        <div class="receipt-summary">
            <div><strong>Subtotal:</strong> <span class="float-end">Rp{{ formatNumber(transaction.original_amount) }}</span></div>
            
            <div v-if="transaction.discount_amount > 0">
                <strong>Diskon ({{ transaction.promo_code }}):</strong> 
                <span class="float-end">- Rp{{ formatNumber(transaction.discount_amount) }}</span>
            </div>
            
            <div v-if="transaction.point_discount_amount > 0">
                <strong>Diskon Poin ({{ transaction.points_redeemed }} pts):</strong> 
                <span class="float-end">- Rp{{ formatNumber(transaction.point_discount_amount) }}</span>
            </div>
            
            <hr class="receipt-hr" style="border-style: solid; margin: 5px 0;" />

            <div><strong>Total Akhir:</strong> <span class="float-end">Rp{{ formatNumber(transaction.final_amount) }}</span></div>
            <div><strong>Dibayar:</strong> <span class="float-end">Rp{{ formatNumber(transaction.paid_amount) }}</span></div>
            <div><strong>Kembali:</strong> <span class="float-end">Rp{{ formatNumber(transaction.change_amount) }}</span></div>

            <div v-if="transaction.points_earned > 0" class="mt-3 pt-2" style="border-top: 1px dashed black;">
                <strong>Poin Didapat:</strong> <span class="float-end">+{{ formatNumber(transaction.points_earned) }} Poin</span>
            </div>
        </div>
        <div class="receipt-footer text-center">
            <p>Terima kasih telah berbelanja!</p>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted, nextTick } from "vue";
import { useRoute } from "vue-router";
import ApiService from "@/core/services/ApiService";

interface Product {
    name: string;
}

interface TransactionItem {
    id: number;
    quantity: number;
    price: number;
    product: Product;
}

interface User {
    name: string;
}

interface Member {
    name: string;
}

interface Transaction {
    invoice_number: string;
    created_at: string;
    user: User | null;
    items: TransactionItem[];
    original_amount: number;
    discount_amount: number;
    final_amount: number;
    paid_amount: number;
    change_amount: number;
    promo_code: string | null;
    member: Member | null; 
    points_earned: number;
    points_redeemed: number;
    point_discount_amount: number;
}

const route = useRoute();
const transaction = ref<Transaction>({
    invoice_number: '...',
    created_at: new Date().toISOString(),
    user: { name: '...' },
    items: [],
    original_amount: 0,
    discount_amount: 0,
    final_amount: 0,
    paid_amount: 0,
    change_amount: 0,
    promo_code: null,
    member: null,
    points_earned: 0,
    points_redeemed: 0,
    point_discount_amount: 0,
});

const formatNumber = (value: number | string) => {
    const num = typeof value === 'string' ? parseFloat(value) : value;
    if (isNaN(num)) return '0';
    return new Intl.NumberFormat('id-ID').format(num);
};

onMounted(async () => {
    const transactionId = route.params.id;
    if (transactionId) {
    try {
        const { data } = await ApiService.get(`/transactions/${transactionId}`);
        transaction.value = data;
        
        await nextTick();
        window.print();

    } catch (error) {
        console.error("Failed to fetch transaction details:", error);
        alert("Gagal memuat detail transaksi.");
    }
    }
});
</script>

<style>
/* Sembunyikan semua elemen lain saat mencetak */
@media print {
    body * {
    visibility: hidden;
    }
    .receipt-container, .receipt-container * {
    visibility: visible;
    }
    .receipt-container {
    position: absolute;
    left: 0;
    top: 0;
    width: 100%;
    }
}

/* Styling dasar untuk struk */
.receipt-container {
    width: 300px; /* Lebar struk thermal printer */
    margin: 20px auto;
    font-family: 'Courier New', Courier, monospace;
    font-size: 12px;
    color: #000;
}
.receipt-hr {
    border-top: 1px dashed black;
}
.receipt-table {
    width: 100%;
    border-collapse: collapse;
}
.receipt-table th, .receipt-table td {
    padding: 5px 0;
}
.text-center { text-align: center; }
.text-end { text-align: right; }
.float-end { float: right; }
.receipt-summary > div {
    margin-bottom: 5px;
    overflow: hidden; /* Fix float issue */
}
.receipt-footer {
    margin-top: 20px;
}
</style>
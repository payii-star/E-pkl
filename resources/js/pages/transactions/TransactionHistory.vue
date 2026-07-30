<template>
    <div class="card">
    <div class="card-header border-0 pt-6">
        <div class="card-title">
        <h2>Transaction History</h2>
        </div>
    </div>
    <div class="card-body pt-0">
        <table class="table align-middle table-row-dashed fs-6 gy-5">
            <thead>
                <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
                    <th>Invoice #</th>
                    <th>Cashier</th>
                    <th>Pelanggan</th>
                    <th>Diskon Promo</th>
                    <th>Diskon Poin</th>
                    <th>Poin Didapat</th>
                    <th>Final Amount</th>
                    <th>Date</th>
                    <th class="text-end">Details</th>
                </tr>
            </thead>
            <tbody class="fw-semibold text-gray-600">
                <tr v-for="transaction in transactions" :key="transaction.id">
                    <td>
                        <a :href="`/print/receipt/${transaction.invoice_number}`" target="_blank">{{ transaction.invoice_number }}</a>
                    </td>
                    <td>{{ transaction.user ? transaction.user.name : 'N/A' }}</td>
                    <td>{{ transaction.member ? transaction.member.name : '-' }}</td>
                    
                    <td>
                        {{ transaction.discount_amount > 0 ? `Rp ${new Intl.NumberFormat('id-ID').format(transaction.discount_amount)}` : '-' }}
                    </td>

                    <td>
                        {{ transaction.point_discount_amount > 0 ? `Rp ${new Intl.NumberFormat('id-ID').format(transaction.point_discount_amount)}` : '-' }}
                    </td>

                    <td>{{ transaction.points_earned > 0 ? `+${transaction.points_earned}` : '-' }}</td>
                    <td>Rp {{ new Intl.NumberFormat('id-ID').format(transaction.final_amount) }}</td>
                    <td>{{ new Date(transaction.created_at).toLocaleString('id-ID') }}</td>
                    <td class="text-end">
                        <a :href="`/print/receipt/${transaction.invoice_number}`" target="_blank" class="btn btn-light btn-sm">
                        View
                        </a>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from "vue";
import ApiService from "@/core/services/ApiService";

interface User {
    id: number;
    name: string;
}

interface Member {
    id: number;
    name: string;
}

interface Transaction {
    id: number;
    invoice_number: string;
    user: User | null;
    created_at: string;
    original_amount: number;
    discount_amount: number;
    point_discount_amount: number;
    final_amount: number;
    member: Member | null;
    points_earned: number;
}

const transactions = ref<Transaction[]>([]);

const fetchHistory = () => {
    ApiService.get("/transactions/history")
    .then(({ data }) => {
        transactions.value = data.data;
    });
};

onMounted(() => {
    fetchHistory();
});
</script>
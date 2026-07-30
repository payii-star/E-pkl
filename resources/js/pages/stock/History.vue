<template>
    <div class="card">
    <div class="card-header border-0 pt-6">
        <div class="card-title">
        <h2>Stock Movement History</h2>
        </div>
    </div>
    <div class="card-body pt-0">
        <div class="row mb-5">
        <div class="col-md-4">
            <label class="form-label">Start Date:</label>
            <input type="date" class="form-control" v-model="filters.start_date" />
        </div>
        <div class="col-md-4">
            <label class="form-label">End Date:</label>
            <input type="date" class="form-control" v-model="filters.end_date" />
        </div>
        <div class="col-md-4 d-flex align-items-end">
            <button class="btn btn-primary me-2" @click="fetchHistory(1)">
            <KTIcon icon-name="filter" icon-class="fs-2" /> Filter
            </button>
            <button class="btn btn-light-primary" @click="exportData">
            <KTIcon icon-name="exit-up" icon-class="fs-2" /> Export CSV
            </button>
        </div>
        </div>

        <div class="table-responsive">
        <table class="table align-middle table-row-dashed fs-6 gy-5">
            <thead>
            <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
                <th>Tanggal</th>
                <th>Produk / Varian</th>
                <th>Perubahan</th>
                <th>Tipe</th>
                <th>Oleh</th>
                <th>Catatan</th>
            </tr>
            </thead>
            <tbody class="fw-semibold text-gray-600">
            <tr v-for="item in stockMovements" :key="item.id">
                <td>{{ formatDate(item.created_at) }}</td>
                <td>
                <div>{{ item.product_variant.product.name }}</div>
                <div class="text-muted fs-7">{{ formatVariant(item.product_variant.options) }}</div>
                </td>
                <td>
                <span :class="item.quantity_change > 0 ? 'text-success' : 'text-danger'" class="fw-bold">
                    {{ item.quantity_change > 0 ? `+${item.quantity_change}` : item.quantity_change }}
                </span>
                </td>
                <td>
                <span class="badge" :class="getBadgeClass(item.type)">{{ item.type }}</span>
                </td>
                <td>{{ item.user ? item.user.name : 'System' }}</td>
                <td>{{ item.notes }}</td>
            </tr>
            <tr v-if="stockMovements.length === 0">
                <td colspan="6" class="text-center">No data found.</td>
            </tr>
            </tbody>
        </table>
        </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted, reactive } from "vue";
import ApiService from "@/core/services/ApiService";

// --- Interfaces ---
interface Product { name: string; }
interface ProductVariant {
product: Product;
options: object;
}
interface User { name: string; }
interface StockMovement {
id: number;
created_at: string;
product_variant: ProductVariant;
quantity_change: number;
type: string;
user: User | null;
notes: string | null;
}

// --- State ---
const stockMovements = ref<StockMovement[]>([]);
const filters = reactive({
start_date: '',
end_date: '',
});

// --- Functions ---
const fetchHistory = (page = 1) => {
const params = {
    page,
    ...filters
};

// PERBAIKAN: Gunakan 'query' bukan 'get'
ApiService.query("/master/stock/history", params)
    .then(({ data }) => {
    stockMovements.value = data.data;
    });
};

const exportData = () => {
// PERBAIKAN: Gunakan 'query' bukan 'get'. Untuk ekspor, kita butuh config tambahan.
// Karena 'query' tidak mendukung config tambahan seperti 'responseType', kita panggil axios langsung.
// Ini menunjukkan batasan pada ApiService.query saat ini, tapi bisa kita atasi.

ApiService.setHeader(); // Pastikan header otentikasi di-set
ApiService.vueInstance.axios.get('/master/stock/history/export', {
    params: filters,
    responseType: 'blob',
}).then((response) => {
    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement('a');
    link.href = url;
    
    const contentDisposition = response.headers['content-disposition'];
    let fileName = 'report.csv';
    if (contentDisposition) {
        const fileNameMatch = contentDisposition.match(/filename="(.+)"/);
        if (fileNameMatch && fileNameMatch.length === 2) fileName = fileNameMatch[1];
    }

    link.setAttribute('download', fileName);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
});
};

const formatDate = (dateString: string) => {
if (!dateString) return 'N/A';
return new Date(dateString).toLocaleString('id-ID', {
    dateStyle: 'medium',
    timeStyle: 'short',
});
};

const formatVariant = (options: object) => {
if (!options) return '';
return Object.values(options).join(' / ');
};

const getBadgeClass = (type: string) => {
if (type === 'sale') return 'badge-light-danger';
if (type === 'stock_in') return 'badge-light-success';
if (type === 'adjustment') return 'badge-light-warning';
return 'badge-light-primary';
};

onMounted(() => {
fetchHistory();
});
</script>
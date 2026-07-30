    <template>
        <div class="card">
        <div class="card-header border-0 pt-6">
            <div class="card-title">
            <h2>Stock Management Overview</h2>
            </div>
        </div>
        <div class="card-body pt-0">
            <div class="table-responsive">
            <table class="table align-middle table-row-dashed fs-6 gy-5">
                <thead>
                <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
                    <th class="min-w-150px">Product Name</th>
                    <th class="min-w-125px">Variant</th>
                    <th class="min-w-100px">SKU</th>
                    <th class="min-w-100px text-center">Physical Stock</th>
                    <th class="min-w-100px text-center">Reserved</th>
                    <th class="min-w-100px text-center fw-bolder">Available</th>
                    <th class="text-end min-w-70px">Status</th>
                </tr>
                </thead>
                <tbody class="fw-semibold text-gray-600">
                <tr v-for="variant in variants" :key="variant.id">
                    <td>{{ variant.product.name }}</td>
                    <td>{{ getVariantName(variant.options) }}</td>
                    <td>{{ variant.sku }}</td>
                    <td class="text-center">{{ variant.stock }}</td>
                    <td class="text-center">{{ variant.reserved_stock }}</td>
                    <td class="text-center fw-bolder">{{ variant.available_stock }}</td>
                    <td class="text-end">
                    <span :class="getStatusClass(variant.available_stock ?? 0)" class="badge fw-bold">
                        
                        {{ getStatusText(variant.available_stock ?? 0) }}

                    </span>
                    </td>
                </tr>
                </tbody>
            </table>
            </div>
        </div>
        </div>
    </template>
    
    <script setup lang="ts">
    import { ref, onMounted } from "vue";
    import ApiService from "@/core/services/ApiService";
    import type { ProductVariant } from "@/types/products";
    
    const variants = ref<ProductVariant[]>([]);
    
    const fetchVariants = () => {
        // PERUBAHAN 1: Panggil endpoint API yang baru
        ApiService.get("/master/stock-overview")
        .then(({ data }) => {
            // Laravel pagination meletakkan data di dalam properti 'data'
            variants.value = data.data;
        });
    };
    
    const getVariantName = (options: object) => {
        if (!options) return '';
        return Object.values(options).join(' / ');
    };
    
    // PERUBAHAN 2: Gunakan 'available_stock' untuk menentukan status
    const getStatusClass = (availableStock: number) => {
        if (availableStock > 20) return "badge-light-success";
        if (availableStock > 0) return "badge-light-warning";
        return "badge-light-danger";
    };
    
    const getStatusText = (availableStock: number) => {
        if (availableStock > 20) return "In Stock";
        if (availableStock > 0) return "Low Stock";
        return "Out of Stock";
    };
    
    onMounted(() => {
        fetchVariants();
    });
    </script>
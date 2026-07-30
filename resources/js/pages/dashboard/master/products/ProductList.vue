    <template>
        <div class="card">
        <div class="card-header border-0 pt-6">
            <div class="card-title">
            <h2>Product Management</h2>
            </div>
            <div class="card-toolbar">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#kt_modal_add_product">
                <KTIcon icon-name="plus" icon-class="fs-2" />
                Add Product
            </button>
            </div>
        </div>
        <div class="card-body pt-0">
            <table class="table align-middle table-row-dashed fs-6 gy-5">
            <thead>
                <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
                <th class="min-w-200px">Product</th>
                <th>Category</th>
                <th>Variants</th>
                <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody class="fw-semibold text-gray-600">
                <tr v-for="product in products" :key="product.id">
                
                <td>
                    <div class="d-flex align-items-center">
                    <div class="symbol symbol-50px me-5">
                        <img :src="product.image_url" class="object-fit-cover" :alt="product.name" />
                    </div>
                    <div class="d-flex justify-content-start flex-column">
                        <span class="text-dark fw-bold text-hover-primary fs-6">
                        {{ product.name }}
                        </span>
                    </div>
                    </div>
                </td>
                <td>{{ product.category ? product.category.name : '-' }}</td>
                <td>
                    <span class="badge badge-light">{{ product.variants.length }} Variant(s)</span>
                </td>
                <td class="text-end">
                    <a @click="openEditModal(product)" href="#" class="btn btn-icon btn-bg-light btn-active-color-primary btn-sm me-1" data-bs-toggle="modal" data-bs-target="#kt_modal_edit_product">
                    <KTIcon icon-name="pencil" icon-class="fs-3" />
                    </a>
                    <a @click="deleteProduct(product)" href="#" class="btn btn-icon btn-bg-light btn-active-color-primary btn-sm">
                    <KTIcon icon-name="trash" icon-class="fs-3" />
                    </a>
                </td>
                </tr>
            </tbody>
            </table>
        </div>
        </div>
    
        <ProductAddModal @product-added="fetchProducts" />
        <ProductEditModal :product="productToEdit" @product-updated="fetchProducts" />
    </template>
        
        <script setup lang="ts">
        import { ref, onMounted } from "vue";
        import ApiService from "@/core/services/ApiService";
        import Swal from "sweetalert2";
        import ProductAddModal from "./ProductAddModal.vue";
        import ProductEditModal from "./ProductEditModal.vue";
        
        // IMPOR TIPE DARI FILE TERPUSAT
        import type { Product } from "@/types/products";
        
        // --- State ---
        const products = ref<Product[]>([]);
        const productToEdit = ref<Product | null>(null);
        
        // --- Functions ---
        const fetchProducts = () => {
        ApiService.get("/master/products")
            .then(({ data }) => {
            const productsData = data.data || [];
            productsData.forEach(p => {
                if (!p.variants) p.variants = [];
            });
            products.value = productsData;
            })
            .catch(({ response }) => {
            console.error("Error fetching products:", response);
            products.value = [];
            });
        };
        
        const openEditModal = (product: Product) => {
        productToEdit.value = product;
        };
        
        const deleteProduct = (product: Product) => {
            Swal.fire({
                text: `Are you sure you want to delete "${product.name}" and all its variants?`,
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Yes, delete!",
            }).then(async (result) => {
                if (result.isConfirmed) {
                    await ApiService.delete(`/master/products/${product.id}`);
                    Swal.fire("Deleted!", "The product has been deleted.", "success");
                    fetchProducts();
                }
            });
        };
        
        onMounted(() => {
        fetchProducts();
        });
        </script>
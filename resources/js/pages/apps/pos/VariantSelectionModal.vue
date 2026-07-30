    <template>
        <div class="modal fade" id="kt_modal_variant_selection" ref="variantModalRef" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
            <div class="modal-header">
                <h3 class="fw-bold">{{ product?.name }}</h3>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal"><KTIcon icon-name="cross" icon-class="fs-1" /></div>
            </div>
            <div class="modal-body py-10 px-lg-12">
                <div v-if="!product"><p>Loading...</p></div>
                <div v-else>
                <div v-for="(options, variantName) in organizedVariants" :key="variantName" class="mb-7">
                    <h5 class="mb-4 fw-bold text-gray-800">Pilih {{ variantName }}</h5>
                    <div class="d-flex flex-wrap gap-3">
                    <label
                        v-for="option in options"
                        :key="option"
                        class="btn btn-outline btn-outline-dashed btn-active-light-primary flex-center min-w-100px px-4 py-3"
                        :class="{ 'active': isSelected(variantName, option) }"
                    >
                        <input
                        type="radio"
                        class="btn-check"
                        :name="variantName"
                        :value="option"
                        autocomplete="off"
                        @change="selectOption(variantName, option)"
                        />
                        <span class="fw-semibold text-gray-700">{{ option }}</span>
                    </label>
                    </div>
                </div>
                
                <div class="separator separator-dashed my-8"></div>
                
                <div class="d-flex justify-content-between align-items-center">
                    <span class="fw-semibold text-gray-600 fs-5">
                    {{ isVariantSelected ? 'Price:' : 'Total Stock:' }}
                    </span>
                    
                    <span v-if="isVariantSelected && matchingVariant" class="fw-bolder text-dark fs-4">
                    Rp {{ new Intl.NumberFormat('id-ID').format(matchingVariant.price) }}
                    </span>
                    <span v-else class="fw-bolder text-dark fs-4">{{ totalStock }}</span>
                </div>
    
                <div v-if="isVariantSelected && matchingVariant" class="d-flex justify-content-between align-items-center mt-3">
                    <span class="fw-semibold text-gray-600 fs-5">Stock:</span>
                    <span class="fw-bolder text-dark fs-4">{{ matchingVariant.stock }}</span>
                </div>
                </div>
            </div>
            <div class="modal-footer flex-center">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" @click="handleAddToCart" :disabled="!isVariantSelected">
                Add to Cart
                </button>
            </div>
            </div>
        </div>
        </div>
    </template>
    
    <script setup lang="ts">
    import { ref, computed, watch } from "vue";
    import { hideModal } from "@/core/helpers/modal";
    import type { Product, ProductVariant } from "@/types/products";
    
    const props = defineProps<{ product: Product | null }>();
    const emit = defineEmits(['add-to-cart']);
    const variantModalRef = ref<HTMLElement | null>(null);
    const selectedOptions = ref<{ [key: string]: string }>({});
    
    // --- Watcher untuk mereset pilihan saat produk berubah ---
    watch(() => props.product, () => {
        selectedOptions.value = {};
    });
    
    // --- Computed property untuk mengelompokkan varian ---
    const organizedVariants = computed<Record<string, string[]>>(() => {
        if (!props.product?.variants) return {};
        const organized: Record<string, Set<string>> = {};
    
        props.product.variants.forEach(variant => {
        for (const key in variant.options) {
            if (!organized[key]) organized[key] = new Set<string>();
            organized[key].add(variant.options[key]);
        }
        });
    
        // Ubah Set menjadi Array untuk di-render
        const result: Record<string, string[]> = {};
        for (const key in organized) {
        result[key] = Array.from(organized[key]);
        }
        return result;
    });
    
    // --- Fungsi untuk memilih opsi ---
    const selectOption = (variantName: string, option: string) => {
        selectedOptions.value[variantName] = option;
    };
    
    const isSelected = (variantName: string, option: string) => {
        return selectedOptions.value[variantName] === option;
    };
    
    // ### KUNCI PENYEMPURNAAN ###
    
    // 1. Computed property untuk MENCARI varian yang cocok secara reaktif
    const matchingVariant = computed<ProductVariant | null>(() => {
        if (!props.product) return null;
        const requiredKeys = Object.keys(organizedVariants.value);
        // Pastikan semua opsi yang diperlukan sudah dipilih
        if (requiredKeys.length === 0 && props.product.variants.length === 1) {
        return props.product.variants[0];
        }
        if (!requiredKeys.every(key => selectedOptions.value[key])) {
        return null;
        }
    
        // Cari varian yang cocok dengan semua pilihan
        const found = props.product.variants.find(variant => 
        requiredKeys.every(key => variant.options[key] === selectedOptions.value[key])
        );
        return found || null;
    });
    
    // 2. Computed property untuk MENGECEK apakah varian sudah dipilih (jadi lebih simpel)
    const isVariantSelected = computed(() => !!matchingVariant.value);
    
    // 3. Computed property untuk MENGHITUNG total stok
    const totalStock = computed(() => {
        if (!props.product) return 0;
        return props.product.variants.reduce((total, variant) => total + variant.stock, 0);
    });
    
    // 4. Fungsi Add to Cart menjadi lebih simpel
    const handleAddToCart = () => {
        if (matchingVariant.value) {
        emit('add-to-cart', matchingVariant.value);
        if(variantModalRef.value) hideModal(variantModalRef.value);
        } else {
        // Seharusnya tidak akan terjadi karena tombol disabled, tapi untuk jaga-jaga
        alert("Varian tidak valid!");
        }
    };
    </script>
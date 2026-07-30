<template>
    <div
    class="modal fade"
    id="kt_modal_add_product"
    ref="addProductModalRef"
    tabindex="-1"
    aria-hidden="true"
    >
    <div class="modal-dialog modal-dialog-centered mw-900px">
        <div class="modal-content">
        <div class="modal-header">
            <h2 class="fw-bold">Add a Product</h2>
            <div
            data-bs-dismiss="modal"
            class="btn btn-icon btn-sm btn-active-icon-primary"
            >
            <KTIcon icon-name="cross" icon-class="fs-1" />
            </div>
        </div>
        <div class="modal-body py-10 px-lg-17">
            <form @submit.prevent="submit">
            <div class="row mb-5">
                <div class="col-md-8">
                <label class="required fs-6 fw-semibold mb-2"
                    >Product Name</label
                >
                <input
                    type="text"
                    v-model="product.name"
                    class="form-control"
                    required
                />
                </div>
                <div class="col-md-4">
                <label class="required fs-6 fw-semibold mb-2">Category</label>
                <select
                    v-model="product.category_id"
                    class="form-select"
                    required
                >
                    <option
                    v-for="cat in categories"
                    :key="cat.id"
                    :value="cat.id"
                    >
                    {{ cat.name }}
                    </option>
                </select>
                </div>
            </div>

            <div class="row mb-5">
                <div class="col-12">
                <label class="fs-6 fw-semibold mb-2">Product Image</label>
                <input
                    type="file"
                    @change="handleFileUpload"
                    class="form-control"
                    accept="image/*"
                />
                </div>
            </div>

            <div v-if="generatedVariants.length === 0" class="row mb-5">
                <div class="col-md-4">
                <label class="required fs-6 fw-semibold mb-2">SKU</label>
                <input
                    type="text"
                    v-model="product.sku"
                    class="form-control"
                />
                </div>
                <div class="col-md-4">
                <label class="required fs-6 fw-semibold mb-2">Price</label>
                <input
                    type="text"
                    v-model="formattedSimplePrice"
                    class="form-control"
                />
                </div>
                <div class="col-md-4">
                <label class="required fs-6 fw-semibold mb-2">Stock</label>
                <input
                    type="number"
                    v-model.number="product.stock"
                    class="form-control"
                />
                </div>
            </div>

            <div class="separator separator-dashed my-8"></div>

            <h3 class="mb-5 fs-4">Product Variants (Optional)</h3>
            <div
                v-for="(pv, pv_index) in productVariants"
                :key="pv_index"
                class="row mb-5"
            >
                <div class="col-md-3">
                <select
                    v-model="pv.variant_id"
                    @change="generateVariants"
                    class="form-select"
                >
                    <option :value="null">Select Variant Type</option>
                    <option
                    v-for="variant in allVariants"
                    :key="variant.id"
                    :value="variant.id"
                    >
                    {{ variant.name }}
                    </option>
                </select>
                </div>
                <div class="col-md-8">
                <div class="d-flex flex-wrap gap-2">
                    <span
                    v-for="option in getOptionsForVariant(pv.variant_id)"
                    :key="option.id"
                    >
                    <label
                        class="btn btn-outline btn-outline-dashed btn-active-light-primary btn-sm d-flex align-items-center"
                    >
                        <input
                        type="checkbox"
                        class="me-2"
                        :value="option"
                        v-model="pv.selectedOptions"
                        @change="generateVariants"
                        />
                        {{ option.name }}
                    </label>
                    </span>
                </div>
                </div>
                <div class="col-md-1">
                <button
                    type="button"
                    @click="removeVariantType(pv_index)"
                    class="btn btn-sm btn-icon btn-light-danger"
                >
                    &times;
                </button>
                </div>
            </div>
            <button
                type="button"
                @click="addVariantType"
                class="btn btn-sm btn-light-primary mb-5"
            >
                Add Variant Type
            </button>

            <div class="separator separator-dashed my-8"></div>

            <div v-if="generatedVariants.length > 0">
                <h3 class="mb-5">
                Product Variants ({{ generatedVariants.length }} combinations)
                </h3>
                <table class="table align-middle table-row-dashed fs-6 gy-5">
                <thead>
                    <tr>
                    <th>Variant</th>
                    <th>SKU</th>
                    <th>Price</th>
                    <th>Stock</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                    v-for="(variant, gv_index) in generatedVariants"
                    :key="gv_index"
                    >
                    <td class="fw-bold">{{ variant.name }}</td>
                    <td>
                        <input
                        type="text"
                        v-model="variant.sku"
                        class="form-control form-control-sm"
                        required
                        />
                    </td>
                    <td>
                        <input
                        type="text"
                        :value="formatRupiah(variant.price)"
                        @input="updateVariantPrice(variant, $event)"
                        class="form-control form-control-sm"
                        required
                        />
                    </td>
                    <td>
                        <input
                        type="number"
                        v-model.number="variant.stock"
                        class="form-control form-control-sm"
                        required
                        />
                    </td>
                    </tr>
                </tbody>
                </table>
            </div>

            <div class="modal-footer flex-center">
                <button
                type="reset"
                @click="closeModal"
                data-bs-dismiss="modal"
                class="btn btn-light me-3"
                >
                Cancel
                </button>
                <button type="submit" class="btn btn-primary">Submit</button>
            </div>
            </form>
        </div>
        </div>
    </div>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted, reactive, computed } from "vue";
import ApiService from "@/core/services/ApiService";
import Swal from "sweetalert2";
import { hideModal } from "@/core/helpers/modal";
import type { Product } from "@/types/products";

// --- Interfaces ---
interface Category {
    id: number;
    name: string;
}
interface VariantOption {
    id: number;
    name: string;
}
interface Variant {
    id: number;
    name: string;
    options: VariantOption[];
}

const emit = defineEmits(["product-added"]);
// --- State ---
const categories = ref<Category[]>([]);
const allVariants = ref<Variant[]>([]);
const addProductModalRef = ref<HTMLElement | null>(null);

const product = reactive({
    name: "",
    category_id: null as number | null,
    sku: "",
    price: 0,
    stock: 0,
});

const productVariants = ref<
    { variant_id: number | null; selectedOptions: VariantOption[] }[]
>([]);
const generatedVariants = ref<
    {
    name: string;
    sku: string;
    price: number;
    stock: number;
    options: { [key: string]: string };
    }[]
>([]);
const imageFile = ref<File | null>(null);

// BARU: Helper functions untuk format Rupiah
const formatRupiah = (value: number) => {
    if (!value) return "0";
    return new Intl.NumberFormat("id-ID").format(value);
};

const parseRupiah = (value: string) => {
    if (!value) return 0;
    return parseInt(value.replace(/[^0-9]/g, ""), 10) || 0;
};

// BARU: Computed property untuk harga produk sederhana
const formattedSimplePrice = computed({
    get: () => formatRupiah(product.price),
    set: (newValue) => {
    product.price = parseRupiah(newValue);
    },
});

// BARU: Method untuk update harga varian
const updateVariantPrice = (variant: any, event: Event) => {
    const target = event.target as HTMLInputElement;
    const parsedValue = parseRupiah(target.value);
    variant.price = parsedValue;

    // Trik untuk menjaga format saat user mengetik
    target.value = formatRupiah(parsedValue);
};

// --- API Calls ---
const fetchCategories = () =>
    ApiService.get("/master/categories").then(({ data }) => {
    categories.value = data;
    });
const fetchAllVariants = () =>
    ApiService.get("/master/variants").then(({ data }) => {
    allVariants.value = data;
    });

// --- File Upload ---
const handleFileUpload = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files.length > 0) {
    imageFile.value = target.files[0];
    }
};

// --- Variant Logic ---
const addVariantType = () => {
    productVariants.value.push({ variant_id: null, selectedOptions: [] });
};
const removeVariantType = (index: number) => {
    productVariants.value.splice(index, 1);
    generateVariants();
};

const getOptionsForVariant = (variantId: number | null) => {
    if (!variantId) return [];
    const variant = allVariants.value.find((v) => v.id === variantId);
    return variant ? variant.options : [];
};

const generateVariants = () => {
    const activeProductVariants = productVariants.value.filter(
    (pv) => pv.variant_id && pv.selectedOptions.length > 0
    );
    if (activeProductVariants.length === 0) {
    generatedVariants.value = [];
    return;
    }
    const optionsArrays = activeProductVariants.map((pv) => pv.selectedOptions);
    const combinations = optionsArrays.reduce(
    (acc: VariantOption[][], current: VariantOption[]) => {
        if (acc.length === 0) {
        return current.map((item) => [item]);
        }
        return acc.flatMap((c: VariantOption[]) =>
        current.map((item) => [...c, item])
        );
    },
    [] as VariantOption[][]
    );
    generatedVariants.value = combinations.map((combo: VariantOption[]) => {
    const variantNames = combo.map((opt) => opt.name);
    const optionsObject: { [key: string]: string } = {};
    combo.forEach((option) => {
        for (const variant of allVariants.value) {
        if (variant.options.some((opt) => opt.id === option.id)) {
            optionsObject[variant.name] = option.name;
            break;
        }
        }
    });
    return {
        name: variantNames.join(" / "),
        sku: "",
        price: 0,
        stock: 0,
        options: optionsObject,
    };
    });
};

const closeModal = () => {
    if (addProductModalRef.value) {
    hideModal(addProductModalRef.value);
    }
};

// --- Submit ---
const submit = async () => {
    if (!product.name || !product.category_id) {
    Swal.fire(
        "Validation Error",
        "Product Name and Category are required.",
        "error"
    );
    return;
    }
    if (generatedVariants.value.length === 0) {
    if (!product.sku || !product.price || !product.stock) {
        Swal.fire(
        "Validation Error",
        "SKU, Price, and Stock are required for simple products.",
        "error"
        );
        return;
    }
    }

    const formDataPayload = new FormData();
    formDataPayload.append("name", product.name);
    formDataPayload.append("category_id", String(product.category_id));

    if (imageFile.value) {
    formDataPayload.append("image", imageFile.value);
    }

    if (generatedVariants.value.length > 0) {
    const variantsToSubmit = generatedVariants.value.map((v) => ({
        ...v,
        price: v.price, // ini sudah number
    }));
    formDataPayload.append("variants", JSON.stringify(variantsToSubmit));
    } else {
    formDataPayload.append("sku", product.sku);
    formDataPayload.append("price", String(product.price)); // ini sudah number
    formDataPayload.append("stock", String(product.stock));
    }

    ApiService.post("/master/products", formDataPayload)
    .then(() => {
        Swal.fire({
        text: "Product added successfully!",
        icon: "success",
        buttonsStyling: false,
        confirmButtonText: "Ok, got it!",
        customClass: { confirmButton: "btn btn-primary" },
        });
        emit("product-added");
        closeModal();
    })
    .catch(({ response }) => {
        let message = "An error occurred. Please try again.";
        if (response && response.data && response.data.errors) {
        const errorObject = response.data.errors;
        const errorMessages: string[] = [];
        for (const key in errorObject) {
            if (Object.prototype.hasOwnProperty.call(errorObject, key)) {
            errorMessages.push(...errorObject[key]);
            }
        }
        if (errorMessages.length > 0) {
            message = errorMessages.join("<br>");
        }
        }
        Swal.fire({
        html: message,
        icon: "error",
        buttonsStyling: false,
        confirmButtonText: "Ok, got it!",
        customClass: { confirmButton: "btn btn-primary" },
        });
    });
};

onMounted(() => {
    fetchCategories();
    fetchAllVariants();
});
</script>
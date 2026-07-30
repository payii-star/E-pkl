    <template>
        <div
        class="modal fade"
        id="kt_modal_edit_product"
        ref="editProductModalRef"
        tabindex="-1"
        aria-hidden="true"
        >
        <div class="modal-dialog modal-dialog-centered mw-900px">
            <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">Edit Product</h2>
                <div
                data-bs-dismiss="modal"
                class="btn btn-icon btn-sm btn-active-icon-primary"
                >
                <KTIcon icon-name="cross" icon-class="fs-1" />
                </div>
            </div>
    
            <div class="modal-body py-10 px-lg-17">
                <ul class="nav nav-tabs nav-line-tabs mb-5 fs-6">
                <li class="nav-item">
                    <a
                    class="nav-link active"
                    data-bs-toggle="tab"
                    href="#kt_tab_pane_product_details"
                    >Product Details</a
                    >
                </li>
                <li class="nav-item">
                    <a
                    class="nav-link"
                    data-bs-toggle="tab"
                    href="#kt_tab_pane_stock_management"
                    >Stock Management</a
                    >
                </li>
                </ul>
    
                <div class="tab-content" id="myTabContent">
                <div
                    class="tab-pane fade show active"
                    id="kt_tab_pane_product_details"
                    role="tabpanel"
                >
                    <form @submit.prevent="submit">
                    <div class="mb-10 text-center">
                        <div class="image-input image-input-outline">
                        <div
                            class="image-input-wrapper w-150px h-150px"
                            :style="{
                            backgroundImage: `url(${
                                imagePreviewUrl || existingImageUrl
                            })`,
                            }"
                        ></div>
                        <label
                            class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                            data-kt-image-input-action="change"
                            title="Change image"
                        >
                            <i class="ki-duotone ki-pencil fs-7"
                            ><span class="path1"></span><span class="path2"></span
                            ></i>
                            <input
                            type="file"
                            @change="handleFileUpload"
                            accept=".png, .jpg, .jpeg"
                            />
                        </label>
                        <span
                            v-if="newImageFile"
                            class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                            @click="cancelImageSelection"
                            title="Cancel image"
                        >
                            <i class="ki-duotone ki-cross fs-2"
                            ><span class="path1"></span><span class="path2"></span
                            ></i>
                        </span>
                        </div>
                        <div class="form-text">
                        Allowed file types: png, jpg, jpeg.
                        </div>
                    </div>
    
                    <div class="row mb-5">
                        <div class="col-md-8">
                        <label class="required fs-6 fw-semibold mb-2"
                            >Product Name</label
                        ><input
                            type="text"
                            v-model="productData.name"
                            class="form-control"
                            required
                        />
                        </div>
                        <div class="col-md-4">
                        <label class="required fs-6 fw-semibold mb-2"
                            >Category</label
                        ><select
                            v-model="productData.category_id"
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
    
                    <div v-if="isEditingAsSimpleProduct">
                        <div class="row mb-5">
                        <div class="col-md-4">
                            <label class="required fs-6 fw-semibold mb-2">SKU</label
                            ><input
                            type="text"
                            v-model="productData.sku"
                            class="form-control"
                            required
                            />
                        </div>
                        <div class="col-md-4">
                            <label class="required fs-6 fw-semibold mb-2"
                            >Price</label
                            ><input
                            type="text"
                            v-model="formattedSimplePrice"
                            class="form-control"
                            required
                            />
                        </div>
                        <div class="col-md-4">
                            <label class="required fs-6 fw-semibold mb-2"
                            >Stock</label
                            ><input
                            type="number"
                            v-model.number="productData.stock"
                            class="form-control"
                            required
                            />
                        </div>
                        </div>
                        <div class="separator separator-dashed my-8"></div>
                        <div class="d-flex justify-content-center">
                        <button
                            type="button"
                            @click="switchToVariantMode"
                            class="btn btn-light-primary"
                        >
                            <KTIcon icon-name="plus" icon-class="fs-2" />
                            Add or Manage Variants
                        </button>
                        </div>
                    </div>
    
                    <div v-if="!isEditingAsSimpleProduct">
                        <div class="separator separator-dashed my-8"></div>
                        <h3 class="mb-5 fs-4">Manage Variants</h3>
                        <div
                        v-for="(pv, pv_index) in productVariants"
                        :key="pv_index"
                        class="row mb-5 align-items-center"
                        >
                        <div class="col-md-3">
                            <select
                            v-model="pv.variant_id"
                            @change="generateVariants()"
                            class="form-select form-select-sm"
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
                                    @change="generateVariants()"
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
                        Add Another Variant Type
                        </button>
    
                        <div class="separator separator-dashed my-8"></div>
                        <div v-if="generatedVariants.length > 0">
                        <h3 class="mb-5">
                            Product Variants ({{ generatedVariants.length }}
                            combinations)
                        </h3>
                        <table
                            class="table align-middle table-row-dashed fs-6 gy-5"
                        >
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
                        <button type="submit" class="btn btn-primary">
                        Save Changes
                        </button>
                    </div>
                    </form>
                </div>
    
                <div
                    class="tab-pane fade"
                    id="kt_tab_pane_stock_management"
                    role="tabpanel"
                >
                    <h4>
                    Stock Management for:
                    <span class="text-primary">{{
                        selectedVariantForStock?.name || "Select a Variant"
                    }}</span>
                    </h4>
    
                    <div
                    v-if="!generatedVariants || generatedVariants.length === 0"
                    class="alert alert-warning"
                    >
                    Please save product with at least one variant to manage its
                    stock.
                    </div>
    
                    <div v-if="generatedVariants && generatedVariants.length > 0">
                    <label class="form-label">Select Variant:</label>
                    <select
                        v-model="selectedVariantForStock"
                        class="form-select mb-5"
                    >
                        <option :value="null" disabled>-- Select a variant --</option>
                        <option
                        v-for="variant in generatedVariants"
                        :key="variant.name"
                        :value="variant"
                        >
                        {{ variant.name }} (Current Stock: {{ variant.stock }})
                        </option>
                    </select>
    
                    <div v-if="selectedVariantForStock">
                        <form
                        @submit.prevent="submitStockAdjustment"
                        class="p-4 border rounded mb-8"
                        >
                        <h5 class="mb-4">Apply Stock Change</h5>
                        <div class="row">
                            <div class="col-md-4">
                            <label class="required form-label">Change Type</label>
                            <select
                                v-model="stockForm.type"
                                class="form-select"
                                required
                            >
                                <option value="stock_in">Stock In (+)</option>
                                <option value="adjustment">Adjustment (-)</option>
                            </select>
                            </div>
                            <div class="col-md-4">
                            <label class="required form-label">Quantity</label>
                            <input
                                v-model.number="stockForm.quantity"
                                type="number"
                                class="form-control"
                                required
                                min="1"
                            />
                            </div>
                            <div class="col-md-4">
                            <label class="form-label">Notes</label>
                            <input
                                v-model="stockForm.notes"
                                type="text"
                                class="form-control"
                                placeholder="e.g. From supplier"
                            />
                            </div>
                        </div>
                        <button type="submit" class="btn btn-sm btn-primary mt-4">
                            Apply Change
                        </button>
                        </form>
    
                        <h5>Stock History</h5>
                        <div class="table-responsive">
                        <table
                            class="table table-row-dashed table-row-gray-300 align-middle gs-0 gy-4"
                        >
                            <thead>
                            <tr class="fw-bold text-muted">
                                <th>Date</th>
                                <th>User</th>
                                <th>Type</th>
                                <th>Change</th>
                                <th>Notes</th>
                            </tr>
                            </thead>
                            <tbody v-if="stockHistory.length > 0">
                            <tr v-for="item in stockHistory" :key="item.id">
                                <td>
                                {{ new Date(item.created_at).toLocaleString() }}
                                </td>
                                <td>{{ item.user?.name || "System" }}</td>
                                <td>
                                <span
                                    class="badge"
                                    :class="
                                    item.type === 'stock_in'
                                        ? 'badge-light-success'
                                        : 'badge-light-warning'
                                    "
                                    >{{ item.type }}</span
                                >
                                </td>
                                <td>
                                <span
                                    class="fw-bold"
                                    :class="
                                    item.quantity_change > 0
                                        ? 'text-success'
                                        : 'text-danger'
                                    "
                                    >{{ item.quantity_change > 0 ? "+" : ""
                                    }}{{ item.quantity_change }}</span
                                >
                                </td>
                                <td>{{ item.notes }}</td>
                            </tr>
                            </tbody>
                            <tbody v-else>
                            <tr>
                                <td colspan="5" class="text-center">
                                No stock history for this variant.
                                </td>
                            </tr>
                            </tbody>
                        </table>
                        </div>
                    </div>
                    </div>
                </div>
                </div>
            </div>
            </div>
        </div>
        </div>
    </template>
    
    <script setup lang="ts">
    import { ref, onMounted, reactive, watch, computed } from "vue";
    import ApiService from "@/core/services/ApiService";
    import Swal from "sweetalert2";
    import { hideModal } from "@/core/helpers/modal";
    import type {
        Product,
        Category,
        Variant as MasterVariant,
        VariantOption,
        ProductVariant,
        GeneratedVariant,
    } from "@/types/products";
    
    // Define a type for stock movement items
    interface StockMovement {
        id: number;
        created_at: string;
        user: { name: string } | null;
        type: string;
        quantity_change: number;
        notes: string;
    }
    
    // --- Props & Emits ---
    const props = defineProps<{ product: Product | null }>();
    const emit = defineEmits(["product-updated"]);
    
    // --- State ---
    const categories = ref<Category[]>([]);
    const allVariants = ref<MasterVariant[]>([]);
    const editProductModalRef = ref<HTMLElement | null>(null);
    const isEditingAsSimpleProduct = ref(true);
    
    const productData = reactive({
        id: null as number | null,
        name: "",
        category_id: null as number | null,
        sku: "",
        price: 0,
        stock: 0,
    });
    const productVariants = ref<
        { variant_id: number | null; selectedOptions: VariantOption[] }[]
    >([]);
    const generatedVariants = ref<GeneratedVariant[]>([]);
    const newImageFile = ref<File | null>(null);
    const imagePreviewUrl = ref<string | null>(null);
    const existingImageUrl = ref<string>("/media/svg/files/box.svg");
    
    // --- State Baru untuk Stock Management ---
    const selectedVariantForStock = ref<GeneratedVariant | null>(null);
    const stockHistory = ref<StockMovement[]>([]);
    const stockForm = reactive({
        type: "stock_in",
        quantity: 1,
        notes: "",
    });
    
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
        get: () => formatRupiah(productData.price),
        set: (newValue) => {
        productData.price = parseRupiah(newValue);
        },
    });
    
    // BARU: Method untuk update harga varian
    const updateVariantPrice = (variant: any, event: Event) => {
        const target = event.target as HTMLInputElement;
        const parsedValue = parseRupiah(target.value);
        variant.price = parsedValue;
        target.value = formatRupiah(parsedValue);
    };
    
    // =================================================================
    // PERBAIKAN PADA FUNGSI FETCH API
    // =================================================================
    const fetchCategories = () =>
        ApiService.get("/master/categories").then(({ data }) => {
        // PERBAIKAN: Langsung gunakan 'data' dan pastikan itu array
        categories.value = Array.isArray(data) ? data : [];
        });
    
    const fetchAllVariants = () =>
        ApiService.get("/master/variants").then(({ data }) => {
        // PERBAIKAN: Langsung gunakan 'data' dan pastikan itu array
        allVariants.value = Array.isArray(data) ? data : [];
        });
    
    // --- Logic ---
    watch(
        [() => props.product, allVariants],
        ([newProduct, variants]) => {
        // PERBAIKAN: Tambahkan pengecekan Array.isArray untuk mencegah error
        if (newProduct && Array.isArray(variants) && variants.length > 0) {
            const productCopy = JSON.parse(JSON.stringify(newProduct));
            productData.id = productCopy.id;
            productData.name = productCopy.name;
            productData.category_id = productCopy.category_id;
    
            cancelImageSelection();
            existingImageUrl.value =
            productCopy.image_url || "/media/svg/files/box.svg";
    
            const isInitiallySimple =
            productCopy.variants.length <= 1 &&
            (productCopy.variants[0]?.name === "Default" ||
                productCopy.variants.length === 0);
            isEditingAsSimpleProduct.value = isInitiallySimple;
    
            if (isInitiallySimple) {
            productVariants.value = [];
            generatedVariants.value =
                productCopy.variants.length > 0 ? [...productCopy.variants] : [];
            if (productCopy.variants[0]) {
                const simpleVariant = productCopy.variants[0];
                productData.sku = simpleVariant.sku;
                productData.price = simpleVariant.price;
                productData.stock = simpleVariant.stock;
            } else {
                productData.sku = "";
                productData.price = 0;
                productData.stock = 0;
            }
            } else {
            const variantTypes: {
                [key: string]: {
                variant_id: number;
                selectedOptions: Set<VariantOption>;
                };
            } = {};
            productCopy.variants.forEach((v: ProductVariant) => {
                Object.keys(v.options).forEach((key) => {
                if (!variantTypes[key]) {
                    const masterVariant = allVariants.value.find(
                    (mv) => mv.name === key
                    );
                    if (masterVariant) {
                    variantTypes[key] = {
                        variant_id: masterVariant.id,
                        selectedOptions: new Set(),
                    };
                    }
                }
                if (variantTypes[key]) {
                    const masterOption = allVariants.value
                    .find((mv) => mv.name === key)
                    ?.options.find((opt) => opt.name === v.options[key]);
                    if (masterOption)
                    variantTypes[key].selectedOptions.add(masterOption);
                }
                });
            });
            productVariants.value = Object.values(variantTypes).map((v: any) => ({
                variant_id: v.variant_id,
                selectedOptions: Array.from(v.selectedOptions),
            }));
            generateVariants(productCopy.variants);
            }
    
            selectedVariantForStock.value = null;
            stockHistory.value = [];
        }
        },
        { deep: true }
    );
    
    const switchToVariantMode = () => {
        isEditingAsSimpleProduct.value = false;
        if (productVariants.value.length === 0) {
        productVariants.value.push({ variant_id: null, selectedOptions: [] });
        }
    };
    
    const addVariantType = () =>
        productVariants.value.push({ variant_id: null, selectedOptions: [] });
    const removeVariantType = (index: number) => {
        productVariants.value.splice(index, 1);
        generateVariants();
    };
    
    const getOptionsForVariant = (variantId: number | null) => {
        if (!variantId) return [];
        const variant = allVariants.value.find((v) => v.id === variantId);
        return variant ? variant.options : [];
    };
    
    const handleFileUpload = (event: Event) => {
        const target = event.target as HTMLInputElement;
        if (target.files && target.files.length > 0) {
        const file = target.files[0];
        newImageFile.value = file;
        imagePreviewUrl.value = URL.createObjectURL(file);
        }
    };
    
    const cancelImageSelection = () => {
        newImageFile.value = null;
        imagePreviewUrl.value = null;
        const fileInput = editProductModalRef.value?.querySelector(
        'input[type="file"]'
        ) as HTMLInputElement;
        if (fileInput) fileInput.value = "";
    };
    
    const generateVariants = (initialVariants: ProductVariant[] = []) => {
        const oldGeneratedVariants = [...generatedVariants.value];
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
            if (acc.length === 0) return current.map((item) => [item]);
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
        const existingData =
            oldGeneratedVariants.find(
            (ev) => JSON.stringify(ev.options) === JSON.stringify(optionsObject)
            ) ||
            initialVariants.find(
            (ev) => JSON.stringify(ev.options) === JSON.stringify(optionsObject)
            );
        return {
            id: existingData?.id,
            name: variantNames.join(" / "),
            sku: existingData?.sku || "",
            price: existingData?.price || 0,
            stock: existingData?.stock || 0,
            options: optionsObject,
        };
        });
    };
    
    const closeModal = () => {
        if (editProductModalRef.value) hideModal(editProductModalRef.value);
    };
    
    const submit = async () => {
        const formDataPayload = new FormData();
        formDataPayload.append("name", productData.name);
        if (productData.category_id) {
        formDataPayload.append("category_id", String(productData.category_id));
        }
    
        let variantsToSubmit: GeneratedVariant[];
        if (isEditingAsSimpleProduct.value) {
        variantsToSubmit = [
            {
            id: generatedVariants.value[0]?.id || null,
            name: "Default",
            sku: productData.sku,
            price: productData.price,
            stock: productData.stock,
            options: generatedVariants.value[0]?.options || {},
            },
        ];
        } else {
        variantsToSubmit = generatedVariants.value;
        }
    
        formDataPayload.append("variants", JSON.stringify(variantsToSubmit));
        if (newImageFile.value) formDataPayload.append("image", newImageFile.value);
        formDataPayload.append("_method", "PUT");
    
        try {
        if (!productData.id) throw new Error("Product ID is missing");
        await ApiService.post(
            `/master/products/${productData.id}`,
            formDataPayload
        );
        Swal.fire("Success", "Product updated successfully!", "success");
        emit("product-updated");
        closeModal();
        } catch (error) {
        Swal.fire(
            "Error",
            "An error occurred while updating the product.",
            "error"
        );
        }
    };
    
    const fetchStockHistory = async (variantId: number) => {
        if (!variantId) {
        stockHistory.value = [];
        return;
        }
        try {
        const { data } = await ApiService.get(
            `/master/variants/${variantId}/stock-history`
        );
        stockHistory.value = data.data;
        } catch (error) {
        console.error("Failed to fetch stock history:", error);
        stockHistory.value = [];
        }
    };
    
    const submitStockAdjustment = async () => {
        if (!selectedVariantForStock.value?.id) {
        Swal.fire("Error", "Please select a variant first.", "error");
        return;
        }
    
        const quantityChange =
        stockForm.type === "stock_in"
            ? Math.abs(stockForm.quantity)
            : -Math.abs(stockForm.quantity);
    
        const payload = {
        variant_id: selectedVariantForStock.value.id,
        quantity_change: quantityChange,
        type: stockForm.type,
        notes: stockForm.notes,
        };
    
        try {
        const { data } = await ApiService.post("/master/stock/adjust", payload);
    
        selectedVariantForStock.value.stock = data.data.stock;
    
        Swal.fire("Success", "Stock has been updated!", "success");
        await fetchStockHistory(selectedVariantForStock.value.id);
    
        stockForm.quantity = 1;
        stockForm.notes = "";
        } catch (error) {
        Swal.fire("Error", "Failed to update stock.", "error");
        }
    };
    
    watch(selectedVariantForStock, (newVariant) => {
        if (newVariant?.id) {
        fetchStockHistory(newVariant.id);
        } else {
        stockHistory.value = [];
        }
    });
    
    onMounted(() => {
        fetchCategories();
        fetchAllVariants();
    });
    </script>
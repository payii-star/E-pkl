<template>
    <div class="d-flex flex-row">
    <div class="flex-row-fluid me-lg-10">
        <div class="card">
        <div class="card-header"><h3 class="card-title">Product Catalog</h3></div>
        <div class="card-body">
            <div class="d-flex align-items-center position-relative mb-8">
            <KTIcon icon-name="magnifier" icon-class="fs-1 position-absolute ms-6"/>
            <input type="text" v-model="searchTerm" class="form-control form-control-solid w-100 ps-15" placeholder="Search by product name"/>
            </div>

            <div class="mb-5">
            <label class="form-label">Scan Barcode Produk</label>
            <div class="d-flex">
                <input ref="barcodeInput" type="text" class="form-control form-control-solid" placeholder="Gunakan scanner atau input manual..." v-model="productBarcodeValue" @keyup.enter="handleScanFromInput"/>
                <button class="btn btn-primary ms-2" title="Scan Product with Camera" @click="openScanner('product')">
                    <KTIcon icon-name="barcode" icon-class="fs-2" />
                </button>
            </div>
            </div>
            
            <div class="mb-5">
            <label class="form-label">Cari Member (ID / No. Telepon)</label>
            <div class="d-flex">
                <input type="text" class="form-control form-control-solid me-2" placeholder="Scan ID atau masukkan no. telp..." v-model="memberSearchQuery" @keyup.enter="searchMember" />
                <button class="btn btn-light-primary me-2" @click="searchMember" :disabled="isSearchingMember">
                <span v-if="!isSearchingMember">Cari</span>
                <span v-else class="spinner-border spinner-border-sm"></span>
                </button>
                <button class="btn btn-primary" title="Scan Member ID with Camera" @click="openScanner('member')">
                    <KTIcon icon-name="barcode" icon-class="fs-2" />
                </button>
            </div>
            </div>

            <div v-if="selectedMember" class="d-flex justify-content-between align-items-center bg-light-success rounded p-4 mb-8">
                <div>
                    <div class="text-success fs-7">
                        Pelanggan: <span class="fw-bold">{{ selectedMember.name }}</span>
                    </div>
                    <div class="text-success fs-7">
                        Poin: <span class="fw-bold">{{ selectedMember.points?.toLocaleString('id-ID') || 0 }}</span>
                        (Setara Rp {{ (selectedMember.points * pointValueInRupiah).toLocaleString('id-ID') }})
                    </div>
                </div>
                <a @click="clearMember" href="#" class="btn btn-sm btn-icon btn-light-danger">
                    <KTIcon icon-name="cross" icon-class="fs-2" />
                </a>
            </div>

            <div v-if="selectedMember && selectedMember.points > 0 && redeemedPoints === 0" class="mb-5">
                <button @click="redeemPoints" class="btn btn-sm btn-primary">
                    <KTIcon icon-name="gift" icon-class="fs-3" />
                    Gunakan Poin sebagai Diskon
                </button>
            </div>
            <div v-if="redeemedPoints > 0" class="alert alert-success d-flex justify-content-between">
                <span>Diskon Poin Rp {{ pointDiscountAmount.toLocaleString('id-ID') }} diterapkan.</span>
                <a href="#" @click.prevent="redeemedPoints = 0">Batalkan</a>
            </div>
            
            <div class="row g-6 g-xl-8">
            <div v-for="product in filteredProducts" :key="product.id" class="col-md-4 col-xl-3" @click="openVariantSelector(product)">
                <div class="card h-100 card-flush-hover">
                <div class="card-body d-flex flex-column p-5 text-center">
                    <div class="mb-5">
                    <img :src="product.image_url" class="mh-125px mw-100 rounded" :alt="product.name">
                    </div>
                    <div class="text-dark text-hover-primary fw-bold fs-6">{{ product.name }}</div>
                </div>
                </div>
            </div>
            </div>
        </div>
        </div>
    </div>
    <div class="flex-row-auto w-lg-300px w-xl-350px">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Shopping Cart</h3>
                <div class="card-toolbar">
                <a @click="clearCart()" href="#" class="btn btn-sm btn-light-danger">Clear All</a>
                </div>
            </div>
            <div class="card-body">
                <div v-if="cart.length === 0" class="text-center text-gray-400">Cart is empty</div>
                <div v-else class="table-responsive">
                <table class="table align-middle">
                    <tbody>
                    <tr v-for="item in cart" :key="item.id">
                        <td class="ps-0">
                        <a href="#" class="text-dark fw-bold text-hover-primary mb-1 fs-6">{{ item.product.name }}</a>
                        <span class="text-muted fw-semibold d-block fs-7">{{ getVariantName(item.options) }}</span>
                        </td>
                        <td class="text-end">
                        <div class="input-group input-group-sm" style="width: 100px;">
                            <button class="btn btn-icon btn-light" type="button" @click="decrementQuantity(item)">-</button>
                            <input type="text" class="form-control text-center" readonly :value="item.quantity" />
                            <button class="btn btn-icon btn-light" type="button" @click="incrementQuantity(item)">+</button>
                        </div>
                        </td>
                        <td class="text-end ps-0">
                        <a @click="removeFromCart(item.id)" href="#" class="btn btn-icon btn-sm btn-light-danger"><KTIcon icon-name="trash" icon-class="fs-4" /></a>
                        </td>
                    </tr>
                    </tbody>
                </table>
                </div>
            </div>
            <div class="card-footer">
                <div class="d-flex mb-4">
                <input 
                    type="text" 
                    v-model="promoCodeInput" 
                    class="form-control form-control-sm me-2" 
                    placeholder="Kode Promo"
                    @keyup.enter="applyPromo"
                />
                <button @click="applyPromo" class="btn btn-sm btn-light-primary">Apply</button>
                </div>

                <div class="d-flex justify-content-between mb-2">
                    <span class="fw-semibold text-gray-600">Subtotal</span>
                    <span class="fw-semibold text-dark">Rp {{ new Intl.NumberFormat('id-ID').format(cartTotal) }}</span>
                </div>
                <div v-if="discountAmount > 0" class="d-flex justify-content-between mb-2">
                    <span class="fw-semibold text-gray-600">Diskon ({{ appliedPromo.name }})</span>
                    <span class="fw-semibold text-danger">- Rp {{ new Intl.NumberFormat('id-ID').format(discountAmount) }}</span>
                </div>
                <div v-if="pointDiscountAmount > 0" class="d-flex justify-content-between mb-2">
                    <span class="fw-semibold text-gray-600">Diskon Poin</span>
                    <span class="fw-semibold text-danger">- Rp {{ pointDiscountAmount.toLocaleString('id-ID') }}</span>
                </div>
                <div class="d-flex justify-content-between fw-bold fs-5 mb-4">
                    <span>Total</span>
                    <span>Rp {{ new Intl.NumberFormat('id-ID').format(finalTotal) }}</span>
                </div>
                <hr class="my-4">
                
                <button 
                @click="processPaymentWithMidtrans" 
                :data-kt-indicator="loading ? 'on' : null" 
                class="btn btn-primary w-100" 
                :disabled="cart.length === 0 || isCartInvalid || loading">
                <span v-if="!loading" class="indicator-label">Checkout with Midtrans</span>
                <span v-if="loading" class="indicator-progress">
                    Please wait... <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                </span>
                </button>
            </div>
        </div>
    </div>
    </div>

    <VariantSelectionModal 
    :product="selectedProduct" 
    @add-to-cart="addToCartFromModal" 
    />

    <CameraScanner 
    v-model="isScannerVisible" 
    @scan-success="handleScanSuccess"
    />
</template>
    
<script setup lang="ts">
import { ref, onMounted, computed } from "vue";
import ApiService from "@/core/services/ApiService";
import { Modal } from "bootstrap";
import VariantSelectionModal from "./VariantSelectionModal.vue";
import Swal from "sweetalert2";
import type { Product, ProductVariant, CartItem } from "@/types/products";
import CameraScanner from "@/components/CameraScanner.vue";

// --- State ---
const allProducts = ref<Product[]>([]);
const searchTerm = ref('');
const cart = ref<CartItem[]>([]);
const selectedProduct = ref<Product | null>(null);
const loading = ref(false);
const promoCodeInput = ref('');
const appliedPromo = ref<any>(null);
const barcodeInput = ref<HTMLInputElement | null>(null);
const productBarcodeValue = ref('');
const memberSearchQuery = ref('');
const selectedMember = ref<any>(null);
const isSearchingMember = ref(false);
const isScannerVisible = ref(false);
const scannerContext = ref<'product' | 'member'>('product');

// BARU: State untuk poin
const redeemedPoints = ref(0);
const pointValueInRupiah = 100; // 1 Poin = Rp 100

// --- Computed ---
const filteredProducts = computed(() => {
    if (!searchTerm.value) return allProducts.value;
    return allProducts.value.filter(p => p.name.toLowerCase().includes(searchTerm.value.toLowerCase()));
});
const cartTotal = computed(() => cart.value.reduce((total, item) => total + (item.price * item.quantity), 0));
const isCartInvalid = computed(() => cart.value.some(item => item.quantity > (item.available_stock ?? item.stock)));
const discountAmount = computed(() => {
    if (!appliedPromo.value || cartTotal.value < appliedPromo.value.min_purchase) return 0;
    if (appliedPromo.value.type === 'percentage') return cartTotal.value * (appliedPromo.value.value / 100);
    if (appliedPromo.value.type === 'fixed_amount') return appliedPromo.value.value;
    return 0;
});
// BARU: Computed untuk diskon poin
const pointDiscountAmount = computed(() => {
  return redeemedPoints.value * pointValueInRupiah;
});
// DIUBAH: Final total sekarang juga harus dikurangi diskon poin
const finalTotal = computed(() => {
    const totalAfterDiscounts = cartTotal.value - discountAmount.value - pointDiscountAmount.value;
    return totalAfterDiscounts < 0 ? 0 : totalAfterDiscounts;
});

// --- Functions ---
const fetchProductsForCashier = () => {
    ApiService.get("/master/variants-for-cashier")
    .then(({ data }) => {
        const productsMap = new Map<number, Product>();
        const variantsData = Array.isArray(data) ? data : [];
        for (const variant of variantsData) {
        if (!variant.product) continue;
        if (!productsMap.has(variant.product.id)) {
            productsMap.set(variant.product.id, {
            ...variant.product,
            variants: [],
            });
        }
        productsMap.get(variant.product.id)?.variants.push(variant);
        }
        allProducts.value = Array.from(productsMap.values());
    });
};

const getVariantName = (options: object) => Object.values(options).join(' / ');

const openVariantSelector = (product: Product) => {
    selectedProduct.value = product;
    const modalElement = document.getElementById('kt_modal_variant_selection');
    if (modalElement) {
    const modal = new Modal(modalElement);
    modal.show();
    }
};

const addToCartFromModal = (variant: ProductVariant) => { addToCart(variant); };
const addToCart = (variant: ProductVariant) => {
    const parentProduct = allProducts.value.find(p => p.variants.some(v => v.id === variant.id));
    if (!parentProduct) return;
    const existingItem = cart.value.find(item => item.id === variant.id);
    const currentQuantityInCart = existingItem ? existingItem.quantity : 0;
    const stockToCompare = variant.available_stock ?? variant.stock;
    if (currentQuantityInCart >= stockToCompare) {
    Swal.fire("Stok Habis", "Anda sudah mencapai jumlah stok maksimum yang tersedia.", "warning");
    return;
    }
    if (existingItem) {
    existingItem.quantity++;
    } else {
    cart.value.push({ 
        ...variant, 
        quantity: 1,
        product: { id: parentProduct.id, name: parentProduct.name }
    });
    }
};

const incrementQuantity = (item: CartItem) => {
    const stockToCompare = item.available_stock ?? item.stock;
    if (item.quantity >= stockToCompare) {
    Swal.fire("Stok Habis", "Jumlah item di keranjang sudah mencapai stok maksimum yang tersedia.", "warning");
    return;
    }
    item.quantity++;
};

const decrementQuantity = (item: CartItem) => {
    if (item.quantity > 1) item.quantity--;
    else removeFromCart(item.id);
};

const removeFromCart = (variantId: number) => {
    cart.value = cart.value.filter(item => item.id !== variantId);
};

const clearCart = () => {
    cart.value = [];
    promoCodeInput.value = '';
    appliedPromo.value = null;
    redeemedPoints.value = 0; // DIUBAH: Reset poin
    clearMember();
};

const applyPromo = () => {
    if (!promoCodeInput.value) return;
    const payload = {
    code: promoCodeInput.value,
    member_id: selectedMember.value ? selectedMember.value.id : null,
    };
    ApiService.post('/promos/validate', payload)
    .then(({ data }) => {
        if (cartTotal.value < data.min_purchase) {
        Swal.fire("Gagal", `Promo ini memerlukan pembelian minimum Rp ${new Intl.NumberFormat('id-ID').format(data.min_purchase)}`, "warning");
        appliedPromo.value = null;
        return;
        }
        appliedPromo.value = data;
        Swal.fire("Berhasil", `Promo "${data.name}" berhasil diterapkan!`, "success");
    })
    .catch(({ response }) => {
        appliedPromo.value = null;
        promoCodeInput.value = '';
        Swal.fire("Error", response.data.message || "Kode promo tidak valid.", "error");
    });
};

const searchMember = () => {
    const cleanQuery = memberSearchQuery.value.trim();
    if (!cleanQuery) return;
    isSearchingMember.value = true;

    ApiService.get(`/members/search?query=${cleanQuery}`)
    .then(({ data }) => {
        selectedMember.value = data; // Pastikan data dari API ini mengandung 'points'
        memberSearchQuery.value = ''; 
        Swal.fire("Berhasil", `Member "${data.name}" ditemukan.`, "success");
    })
    .catch(() => {
        selectedMember.value = null;
        memberSearchQuery.value = ''; 
        Swal.fire("Gagal", "Member tidak ditemukan.", "error");
    })
    .finally(() => {
        isSearchingMember.value = false;
    });
};

const clearMember = () => {
    selectedMember.value = null;
    memberSearchQuery.value = '';
    redeemedPoints.value = 0; // DIUBAH: Reset poin
};


// --- Logika Scanner dengan Konteks ---
const openScanner = (context: 'product' | 'member') => {
    scannerContext.value = context;
    isScannerVisible.value = true;
};

const processProductBarcode = async (code: string) => {
    const cleanBarcode = code.trim().replace(/\/$/, '');
    if (!cleanBarcode) return;
    try {
    const { data: productVariant } = await ApiService.get(`/products/barcode/${cleanBarcode}`);
    addToCart(productVariant);
    } catch (error) {
    Swal.fire({ text: "Produk dengan barcode tersebut tidak ditemukan!", icon: "error", toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
    } finally {
    productBarcodeValue.value = '';
    barcodeInput.value?.focus();
    }
};

const handleScanFromInput = () => {
    processProductBarcode(productBarcodeValue.value);
};

const handleScanSuccess = (scannedCode: string) => {
    if (scannerContext.value === 'product') {
    productBarcodeValue.value = scannedCode;
    processProductBarcode(scannedCode);

    } else if (scannerContext.value === 'member') {
    memberSearchQuery.value = scannedCode;
    searchMember();
    }
    
    isScannerVisible.value = false;
};

// BARU: Fungsi untuk menukar poin
const redeemPoints = () => {
    if (!selectedMember.value || selectedMember.value.points <= 0) return;
    let maxDiscountFromPoints = selectedMember.value.points * pointValueInRupiah;
    const currentTotal = cartTotal.value - discountAmount.value;
    if (maxDiscountFromPoints > currentTotal) {
        redeemedPoints.value = Math.floor(currentTotal / pointValueInRupiah);
    } else {
        redeemedPoints.value = selectedMember.value.points;
    }
};


// --- Logika Pembayaran ---
const processPaymentWithMidtrans = () => {
    if (cart.value.length === 0 || isCartInvalid.value) return;
    loading.value = true;
    const payload = {
    cart: cart.value.map(item => ({ id: item.id, quantity: item.quantity })),
    promo_code: appliedPromo.value ? appliedPromo.value.code : null,
    member_id: selectedMember.value ? selectedMember.value.id : null,
    redeemed_points: redeemedPoints.value, // DIUBAH: Kirim poin yang ditukar
    };
    ApiService.post("/payment/create", payload)
    .then(({ data }) => {
        const snapToken = data.snap_token;
        (window as any).snap.pay(snapToken, {
            onSuccess: function(result) {
                Swal.fire("Success!", "Payment completed successfully.", "success");
                const invoiceNumber = result.order_id;
                window.open(`/print/receipt/${invoiceNumber}`, '_blank');
                clearCart();
            },
        onPending: function(result){
            Swal.fire("Pending", "Waiting for payment.", "info");
        },
        onError: function(result){
            Swal.fire("Payment Failed", "An error occurred.", "error");
        },
        onClose: function(){
            console.log('Customer closed the popup without finishing payment');
        }
        });
    })
    .catch(error => {
        console.error(error);
        Swal.fire("Error", "Failed to create payment request.", "error");
    })
    .finally(() => {
        loading.value = false;
    });
};

onMounted(() => {
    fetchProductsForCashier();
    barcodeInput.value?.focus();
});
</script>
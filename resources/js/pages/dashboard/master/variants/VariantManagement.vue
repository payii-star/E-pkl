    <template>
        <div class="card">
        <div class="card-header border-0 pt-6">
            <div class="card-title">
            <h2>Variant Management</h2>
            </div>
            <div class="card-toolbar">
            <button type="button" class="btn btn-primary" @click="openAddVariantModal">
                <KTIcon icon-name="plus" icon-class="fs-2" />
                Add Variant Type
            </button>
            </div>
        </div>
        <div class="card-body pt-0">
            <div v-for="variant in variants" :key="variant.id" class="mb-5">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h4 class="text-gray-700">{{ variant.name }}</h4>
                <div>
                <button @click="openEditVariantModal(variant)" class="btn btn-sm btn-light-primary me-2">Edit Name</button>
                <button @click="deleteVariant(variant)" class="btn btn-sm btn-light-danger">Delete</button>
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <div v-for="option in variant.options" :key="option.id" class="badge badge-light me-2 d-flex align-items-center">
                {{ option.name }}
                <a href="#" @click.prevent="deleteOption(option)" class="text-danger ms-2">×</a>
                </div>
                <form @submit.prevent="addOption(variant)" class="d-inline-block">
                <input
                    type="text"
                    class="form-control form-control-sm d-inline-block"
                    style="width: 100px;"
                    placeholder="Add option"
                    v-model="newOptionName[variant.id]"
                />
                </form>
            </div>
            </div>
        </div>
        </div>
    
        <div class="modal fade" id="kt_modal_variant" ref="variantModalRef" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered mw-450px">
            <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">{{ isEditingVariant ? 'Edit' : 'Add' }} Variant Type</h2>
                <div @click="closeModal" class="btn btn-icon btn-sm btn-active-icon-primary"><KTIcon icon-name="cross" icon-class="fs-1" /></div>
            </div>
            <div class="modal-body">
                <form @submit.prevent="submitVariant">
                <div class="fv-row">
                    <label class="required fs-6 fw-semibold mb-2">Variant Name</label>
                    <input type="text" v-model="variantFormData.name" class="form-control" placeholder="e.g., Ukuran, Warna" required />
                </div>
                <div class="modal-footer flex-center">
                    <button type="reset" @click="closeModal" class="btn btn-light me-3">Cancel</button>
                    <button type="submit" class="btn btn-primary">Submit</button>
                </div>
                </form>
            </div>
            </div>
        </div>
        </div>
    </template>
    
    <script setup lang="ts">
    import { ref, onMounted, reactive } from "vue";
    import ApiService from "@/core/services/ApiService";
    import Swal from "sweetalert2";
    import { Modal } from "bootstrap";
    
    interface VariantOption { id: number; name: string; }
    interface Variant { id: number; name: string; options: VariantOption[]; }
    
    const variants = ref<Variant[]>([]);
    const newOptionName = ref<{ [key: number]: string }>({});
    const isEditingVariant = ref(false);
    const variantModalRef = ref<HTMLElement | null>(null);
    let modal: Modal | null = null;
    
    const variantFormData = reactive({ id: null as number | null, name: "" });
    
    const fetchVariants = () => {
        ApiService.get("/master/variants").then(({ data }) => {
        variants.value = data;
        // Inisialisasi newOptionName untuk setiap varian
        data.forEach(v => { newOptionName.value[v.id] = '' });
        });
    };
    
    const addOption = async (variant: Variant) => {
        const name = newOptionName.value[variant.id];
        if (!name) return;
        await ApiService.post(`/master/variants/${variant.id}/options`, { name });
        fetchVariants(); // Refresh data
    };
    
    const deleteOption = async (option: VariantOption) => {
        await ApiService.delete(`/master/variant-options/${option.id}`);
        fetchVariants();
    };
    
    const openAddVariantModal = () => {
        isEditingVariant.value = false;
        Object.assign(variantFormData, { id: null, name: "" });
        modal?.show();
    };
    
    const openEditVariantModal = (variant: Variant) => {
        isEditingVariant.value = true;
        Object.assign(variantFormData, { id: variant.id, name: variant.name });
        modal?.show();
    };
    
    const closeModal = () => modal?.hide();
    
    const submitVariant = async () => {
        if (isEditingVariant.value) {
        await ApiService.put(`/master/variants/${variantFormData.id}`, { name: variantFormData.name });
        } else {
        await ApiService.post("/master/variants", { name: variantFormData.name });
        }
        fetchVariants();
        closeModal();
    };
    
    const deleteVariant = async (variant: Variant) => {
        Swal.fire({
            text: `Are you sure to delete "${variant.name}" and all its options?`,
            icon: "warning", showCancelButton: true, confirmButtonText: "Yes"
        }).then(async (result) => {
            if (result.isConfirmed) {
                await ApiService.delete(`/master/variants/${variant.id}`);
                fetchVariants();
            }
        });
    };
    
    onMounted(() => {
        fetchVariants();
        if (variantModalRef.value) modal = new Modal(variantModalRef.value);
    });
    </script>
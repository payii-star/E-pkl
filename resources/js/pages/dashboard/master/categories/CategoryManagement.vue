    <template>
        <div class="card">
        <div class="card-header border-0 pt-6">
            <div class="card-title">
            <h2>Category Management</h2>
            </div>
            <div class="card-toolbar">
            <button
                type="button"
                class="btn btn-primary"
                @click="openAddModal"
            >
                <KTIcon icon-name="plus" icon-class="fs-2" />
                Add Category
            </button>
            </div>
        </div>
        <div class="card-body pt-0">
            <table class="table align-middle table-row-dashed fs-6 gy-5">
            <thead>
                <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
                <th>Category Name</th>
                <th>Description</th>
                <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody class="fw-semibold text-gray-600">
                <tr v-for="category in categories" :key="category.id">
                <td>{{ category.name }}</td>
                <td>{{ category.description || '-' }}</td>
                <td class="text-end">
                    <a @click="openEditModal(category)" href="#" class="btn btn-icon btn-bg-light btn-active-color-primary btn-sm me-1">
                    <KTIcon icon-name="pencil" icon-class="fs-3" />
                    </a>
                    <a @click="deleteCategory(category)" href="#" class="btn btn-icon btn-bg-light btn-active-color-primary btn-sm">
                    <KTIcon icon-name="trash" icon-class="fs-3" />
                    </a>
                </td>
                </tr>
            </tbody>
            </table>
        </div>
        </div>
    
        <div class="modal fade" id="kt_modal_category" ref="categoryModalRef" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered mw-650px">
            <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">{{ isEditing ? 'Edit' : 'Add' }} Category</h2>
                <div @click="closeModal" class="btn btn-icon btn-sm btn-active-icon-primary">
                <KTIcon icon-name="cross" icon-class="fs-1" />
                </div>
            </div>
            <div class="modal-body py-10 px-lg-17">
                <el-form @submit.prevent="submit" :model="formData" :rules="rules" ref="formRef">
                <div class="fv-row mb-7">
                    <label class="required fs-6 fw-semibold mb-2">Category Name</label>
                    <el-form-item prop="name">
                    <el-input v-model="formData.name" placeholder="Category Name" />
                    </el-form-item>
                </div>
                <div class="fv-row mb-7">
                    <label class="fs-6 fw-semibold mb-2">Description</label>
                    <el-input v-model="formData.description" type="textarea" placeholder="Description" />
                </div>
                <div class="modal-footer flex-center">
                    <button type="reset" @click="closeModal" class="btn btn-light me-3">Cancel</button>
                    <button :data-kt-indicator="loading ? 'on' : null" class="btn btn-primary" type="submit">
                    <span v-if="!loading" class="indicator-label">Submit</span>
                    <span v-if="loading" class="indicator-progress">
                        Please wait... <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                    </span>
                    </button>
                </div>
                </el-form>
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
    
    interface Category {
        id: number;
        name: string;
        description: string | null;
    }
    
    const categories = ref<Category[]>([]);
    const loading = ref(false);
    const isEditing = ref(false);
    const formRef = ref<any>(null);
    const categoryModalRef = ref<null | HTMLElement>(null);
    let modal: Modal | null = null;
    
    const formData = reactive({
        id: null as number | null,
        name: "",
        description: "",
    });
    
    const rules = ref({
        name: [{ required: true, message: "Category name is required", trigger: "change" }],
    });
    
    const fetchCategories = () => {
        ApiService.get("/master/categories")
        .then(({ data }) => {
            categories.value = data;
        });
    };
    
    const openAddModal = () => {
        isEditing.value = false;
        Object.assign(formData, { id: null, name: "", description: "" });
        modal?.show();
    };
    
    const openEditModal = (category: Category) => {
        isEditing.value = true;
        Object.assign(formData, category);
        modal?.show();
    };
    
    const closeModal = () => {
        modal?.hide();
    };
    
    const submit = async () => {
        if (!formRef.value) return;
        await formRef.value.validate(async (valid) => {
        if (valid) {
            loading.value = true;
            try {
            if (isEditing.value) {
                await ApiService.put(`/master/categories/${formData.id}`, formData);
            } else {
                await ApiService.post("/master/categories", formData);
            }
            Swal.fire("Success", `Category ${isEditing.value ? 'updated' : 'added'} successfully!`, "success");
            fetchCategories();
            closeModal();
            } catch (error) {
            Swal.fire("Error", "An error occurred.", "error");
            } finally {
            loading.value = false;
            }
        }
        });
    };
    
    const deleteCategory = (category: Category) => {
        Swal.fire({
        text: `Are you sure you want to delete ${category.name}?`,
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Yes, delete!",
        cancelButtonText: "No, cancel",
        customClass: { confirmButton: "btn fw-bold btn-danger", cancelButton: "btn fw-bold btn-active-light-primary" },
        }).then(async (result) => {
        if (result.isConfirmed) {
            try {
            await ApiService.delete(`/master/categories/${category.id}`);
            Swal.fire("Deleted!", "The category has been deleted.", "success");
            fetchCategories();
            } catch (error) {
            Swal.fire("Error", "An error occurred while deleting.", "error");
            }
        }
        });
    };
    
    onMounted(() => {
        fetchCategories();
        if (categoryModalRef.value) {
            modal = new Modal(categoryModalRef.value);
        }
    });
    </script>
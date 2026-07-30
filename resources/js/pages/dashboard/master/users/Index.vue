    <template>
        <div class="card">
        <div class="card-header border-0 pt-6">
            <div class="card-title">
            <h2>User Management</h2>
            </div>
            <div class="card-toolbar">
            <button type="button" class="btn btn-primary" @click="openAddModal">
                <KTIcon icon-name="plus" icon-class="fs-2" />
                Add User
            </button>
            </div>
        </div>
        <div class="card-body pt-0">
            <table class="table align-middle table-row-dashed fs-6 gy-5">
            <thead>
                <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
                <th>User</th>
                <th>Role</th>
                <th>Joined Date</th>
                <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody class="fw-semibold text-gray-600">
                <tr v-for="user in users" :key="user.id">
                <td class="d-flex align-items-center">
                    <div class="symbol symbol-circle symbol-50px overflow-hidden me-3">
                        <div class="symbol-label fs-3 bg-light-danger text-danger">{{ user.name.charAt(0) }}</div>
                    </div>
                    <div class="d-flex flex-column">
                        <a href="#" class="text-gray-800 text-hover-primary mb-1">{{ user.name }}</a>
                        <span>{{ user.email }}</span>
                    </div>
                </td>
                <td>
                    <span class="badge badge-light-success">{{ user.roles.length > 0 ? user.roles[0].name : 'No Role' }}</span>
                </td>
                <td>{{ new Date(user.created_at).toLocaleDateString() }}</td>
                <td class="text-end">
                    <a @click="openEditModal(user)" href="#" class="btn btn-icon btn-bg-light btn-active-color-primary btn-sm me-1">
                    <KTIcon icon-name="pencil" icon-class="fs-3" />
                    </a>
                    <a @click="deleteUser(user)" href="#" class="btn btn-icon btn-bg-light btn-active-color-primary btn-sm">
                    <KTIcon icon-name="trash" icon-class="fs-3" />
                    </a>
                </td>
                </tr>
            </tbody>
            </table>
        </div>
        </div>
    
        <div class="modal fade" id="kt_modal_user" ref="userModalRef" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered mw-650px">
            <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">{{ isEditing ? 'Edit' : 'Add' }} User</h2>
                <div @click="closeModal" class="btn btn-icon btn-sm btn-active-icon-primary">
                <KTIcon icon-name="cross" icon-class="fs-1" />
                </div>
            </div>
            <div class="modal-body py-10 px-lg-17">
                <el-form @submit.prevent="submit" :model="formData" :rules="rules" ref="formRef">
                <div class="fv-row mb-7">
                    <label class="required fs-6 fw-semibold mb-2">Full Name</label>
                    <el-form-item prop="name"><el-input v-model="formData.name" /></el-form-item>
                </div>
                <div class="fv-row mb-7">
                    <label class="required fs-6 fw-semibold mb-2">Email</label>
                    <el-form-item prop="email"><el-input v-model="formData.email" /></el-form-item>
                </div>
                <div class="fv-row mb-7">
                    <label class="required fs-6 fw-semibold mb-2">Role</label>
                    <el-form-item prop="role">
                    <el-select v-model="formData.role" placeholder="Select a role" class="w-100">
                        <el-option v-for="role in roles" :key="role.id" :label="role.full_name" :value="role.name"/>
                    </el-select>
                    </el-form-item>
                </div>
                <div class="fv-row mb-7">
                    <label :class="{ 'required': !isEditing }" class="fs-6 fw-semibold mb-2">Password</label>
                    <el-form-item prop="password"><el-input v-model="formData.password" type="password" /></el-form-item>
                    <div v-if="isEditing" class="text-muted fs-7">Leave blank to keep current password.</div>
                </div>
                <div class="modal-footer flex-center">
                    <button type="reset" @click="closeModal" class="btn btn-light me-3">Cancel</button>
                    <button :data-kt-indicator="loading ? 'on' : null" class="btn btn-primary" type="submit">
                    <span v-if="!loading" class="indicator-label">Submit</span>
                    <span v-if="loading" class="indicator-progress">Please wait...</span>
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
    
    // --- Interfaces ---
    interface Role { id: number; name: string; full_name: string; }
    interface User { id: number; name: string; email: string; created_at: string; roles: Role[]; }
    
    // --- State ---
    const users = ref<User[]>([]);
    const roles = ref<Role[]>([]);
    const loading = ref(false);
    const isEditing = ref(false);
    const formRef = ref<any>(null);
    const userModalRef = ref<null | HTMLElement>(null);
    let modal: Modal | null = null;
    
    const formData = reactive({
        id: null as number | null,
        name: "",
        email: "",
        password: "",
        role: "",
    });
    
    const rules = ref({
        name: [{ required: true, message: "Name is required", trigger: "change" }],
        email: [{ required: true, type: 'email', message: "Valid email is required", trigger: "change" }],
        role: [{ required: true, message: "Role is required", trigger: "change" }],
        password: [{ required: true, message: "Password is required", trigger: "change" }],
    });
    
    // --- API Calls ---
    // UBAH BAGIAN INI: Gunakan GET, bukan POST
    const fetchUsers = () => { 
        ApiService.get("/master/users").then(({ data }) => {
        users.value = data.data; 
        }); 
    }; 
    // UBAH BAGIAN INI: Pastikan endpoint untuk role benar
    const fetchRoles = () => { 
        ApiService.get("/master/roles").then(({ data }) => { 
            // Sesuaikan jika struktur data dari RoleController berbeda
            roles.value = data.data || data; 
        }); 
    };
    
    // --- Modal Logic ---
    const openAddModal = () => {
        isEditing.value = false;
        rules.value.password[0].required = true;
        Object.assign(formData, { id: null, name: "", email: "", password: "", role: "" });
        modal?.show();
    };
    
    const openEditModal = (user: User) => {
        isEditing.value = true;
        rules.value.password[0].required = false;
        Object.assign(formData, {
            id: user.id,
            name: user.name,
            email: user.email,
            password: "",
            role: user.roles.length > 0 ? user.roles[0].name : "",
        });
        modal?.show();
    };
    
    const closeModal = () => { modal?.hide(); };
    
    const submit = async () => {
        if (!formRef.value) return;
        await formRef.value.validate(async (valid) => {
        if (valid) {
            loading.value = true;
            try {
            const payload: any = {
                id: formData.id,
                name: formData.name,
                email: formData.email,
                role: formData.role,
            };
            if (formData.password) {
                payload.password = formData.password;
            }
    
            if (isEditing.value) {
                await ApiService.put(`/master/users/${payload.id}`, payload);
            } else {
                await ApiService.post("/master/users", payload); 
            }
            Swal.fire("Success", `User ${isEditing.value ? 'updated' : 'added'}!`, "success");
            fetchUsers();
            closeModal();
            } catch (error) {
            Swal.fire("Error", "An error occurred.", "error");
            } finally {
            loading.value = false;
            }
        }
        });
    };
    
    const deleteUser = (user: User) => {
        Swal.fire({
            text: `Are you sure you want to delete ${user.name}?`,
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Yes, delete!",
            cancelButtonText: "No, cancel",
        }).then(async (result) => {
            if (result.isConfirmed) {
                try {
                    await ApiService.delete(`/master/users/${user.id}`);
                    Swal.fire("Deleted!", "The user has been deleted.", "success");
                    fetchUsers();
                } catch (error) {
                    Swal.fire("Error", "An error occurred.", "error");
                }
            }
        });
    };
    
    onMounted(() => {
        fetchUsers();
        fetchRoles();
        if (userModalRef.value) {
            modal = new Modal(userModalRef.value);
        }
    });
    </script>
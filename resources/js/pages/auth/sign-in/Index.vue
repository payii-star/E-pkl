<template>
    <!--begin::Form-->
    <div class="w-100 sign-in-index">
        <!--begin::Heading-->
        <div class="sign-in-index__header">
            <h2>Masuk ke akun</h2>
            <p>
                Isi email dan kata sandi terdaftar untuk masuk ke
                <strong>{{ setting?.app }}</strong>.
            </p>
        </div>
        <!--end::Heading-->

        <ul class="nav nav-tabs nav-line-tabs mb-5 fs-6" v-if="false">
            <!-- Tab phone disembunyikan untuk sekarang, aktifkan lagi kalau login
                 via nomor telepon sudah didukung backend -->
            <li class="nav-item">
                <a class="nav-link active" data-bs-toggle="tab" href="#with-email">Email</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#with-phone">Telepon</a>
            </li>
        </ul>

        <div class="tab-content" id="myTabContent">
            <div class="tab-pane fade show active" id="with-email" role="tabpanel">
                <WithEmail />
            </div>
        </div>

        <!--begin::Face login link-->
        <div class="text-center mt-4">
            <router-link to="/face-login" class="link-primary fw-bold">
                Masuk dengan Wajah
            </router-link>
        </div>
        <!--end::Face login link-->

        <!--begin::Link-->
        <!-- <div class="text-gray-400 fw-semobold fs-4 text-center">
            {{ $t('login.daftar_label') }}

            <router-link to="/auth/sign-up" class="link-primary fw-bold">
                {{ $t('login.daftar') }}
            </router-link>
        </div> -->
        <!--end::Link-->
    </div>
    <!--end::Form-->
</template>

<script>
import { getAssetPath } from "@/core/helpers/assets";
import { defineComponent, ref } from "vue";
import { useAuthStore } from "@/stores/auth";
import { useRouter } from "vue-router";
import * as Yup from "yup";
import axios from "@/libs/axios";
import { toast } from "vue3-toastify";
import { blockBtn, unblockBtn } from "@/libs/utils";

import WithEmail from "./tabs/WithEmail.vue";
import WithPhone from "./tabs/WithPhone.vue";
import { useSetting } from "@/services";

export default defineComponent({
    name: "sign-in",
    components: {
        WithEmail,
        WithPhone,
    },
    setup() {
        const store = useAuthStore();
        const router = useRouter();
        const { data: setting = {} } = useSetting();

        const submitButton = ref(null);

        //Create form validation object
        const login = Yup.object().shape({
            identifier: Yup.string()
                .email("Email/No. Telepon tidak valid")
                .required("Harap masukkan Email/No. Telepon")
                .label("Email"),
            password: Yup.string()
                .min(8, "Password minimal terdiri dari 8 karakter")
                .required("Harap masukkan password")
                .label("Password"),
        });

        return {
            login,
            submitButton,
            getAssetPath,
            store,
            router,
            setting,
        };
    },
    data() {
        return {
            data: {
                identifier: null,
                password: null,
            },
            check: {
                type: "",
                error: "",
            },
        };
    },
    methods: {
        submit() {
            blockBtn(this.submitButton);

            axios
                .post("/auth/login", { ...this.data, type: this.check.type })
                .then((res) => {
                    this.store.setAuth(res.data.user, res.data.token);
                    this.router.push("/dashboard");
                })
                .catch((error) => {
                    toast.error(error.response.data.message);
                })
                .finally(() => {
                    unblockBtn(this.submitButton);
                });
        },
        checkInput(value) {
            this.check.type = "";
            if (/^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,3})+$/.test(value)) {
                this.check.type = "email";
            } else {
                this.check.type = "phone";
                if (isNaN(this.data.identifier)) {
                    this.check.type =
                        "Masukkan Email / No. Telepon Yang Valid!";
                }
            }
        },
    },
});
</script>

<style scoped>
@import url("https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600&family=Manrope:wght@400;500;600;700;800&display=swap");

.sign-in-index__header {
    margin-bottom: 28px;
}
.sign-in-index__header h2 {
    font-family: "Fraunces", serif;
    font-weight: 500;
    font-size: 27px;
    margin: 0 0 6px;
    color: #152238;
}
.sign-in-index__header p {
    font-family: "Manrope", sans-serif;
    font-size: 14px;
    color: #6b7280;
    margin: 0;
}
.sign-in-index__header p strong {
    color: #152238;
}
</style>
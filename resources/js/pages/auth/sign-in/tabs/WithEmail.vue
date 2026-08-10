<template>
  <VForm class="auth-form" @submit="submit" :validation-schema="login">
    <!--begin::Input group-->
    <div class="auth-field">
      <label class="auth-field__label" for="email">Email</label>
      <div class="auth-field__wrap">
        <span class="auth-field__icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" width="16" height="16">
            <path d="M4 6.5h16v11a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-11Z" stroke="currentColor" stroke-width="1.5"/>
            <path d="m4.5 7 7.5 6 7.5-6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </span>
        <Field
          id="email"
          tabindex="1"
          class="auth-field__input auth-field__input--icon"
          type="text"
          name="email"
          autocomplete="off"
          placeholder="nama@sekolah.sch.id"
          v-model="data.email"
        />
      </div>
      <div class="auth-field__error">
        <ErrorMessage name="email" />
      </div>
    </div>
    <!--end::Input group-->

    <!--begin::Input group-->
    <div class="auth-field">
      <div class="auth-field__label-row">
        <label class="auth-field__label" for="password">Kata sandi</label>
        <!-- <router-link to="/password-reset" class="auth-field__link">Lupa kata sandi?</router-link> -->
      </div>
      <div class="auth-field__wrap">
        <span class="auth-field__icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" width="16" height="16">
            <rect x="5" y="10.5" width="14" height="9.5" rx="2" stroke="currentColor" stroke-width="1.5"/>
            <path d="M8 10.5V7.5a4 4 0 0 1 8 0v3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
          </svg>
        </span>
        <Field
          id="password"
          tabindex="2"
          class="auth-field__input auth-field__input--icon auth-field__input--icon-right"
          type="password"
          name="password"
          v-model="data.password"
          autocomplete="off"
          placeholder="Minimal 8 karakter"
        />
        <button
          type="button"
          class="auth-field__toggle"
          @click="togglePassword"
          aria-label="Tampilkan atau sembunyikan kata sandi"
        >
          <i class="bi bi-eye-slash fs-4"></i>
        </button>
      </div>
      <div class="auth-field__error">
        <ErrorMessage name="password" />
      </div>
    </div>
    <!--end::Input group-->

    <!-- <div class="auth-remember">
      <Field tabindex="3" type="checkbox" id="remember_me" name="remember_me" value="1" v-model="data.remember_me" />
      <label for="remember_me">{{ $t('login.remember') }}</label>
    </div> -->

    <!--begin::Actions-->
    <button tabindex="3" type="submit" ref="submitButton" class="auth-submit">
      <span class="indicator-label">Masuk</span>
      <span class="indicator-progress">
        <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
      </span>
    </button>
    <!--end::Actions-->
  </VForm>
</template>

<script lang="ts">
import { getAssetPath } from "@/core/helpers/assets";
import { defineComponent, ref } from "vue";
import { useAuthStore } from "@/stores/auth";
import { useRouter } from "vue-router";
import * as Yup from "yup";
import axios from "@/libs/axios";
import { toast } from "vue3-toastify"
import { blockBtn, unblockBtn } from "@/libs/utils"

export default defineComponent({
    setup() {
        const store = useAuthStore();
        const router = useRouter();

        const submitButton = ref(null);

        //Create form validation object
        const login = Yup.object().shape({
            email: Yup.string().email('Email tidak valid').required("Harap masukkan Email").label("Email"),
            password: Yup.string().min(8, 'Password minimal terdiri dari 8 karakter').required('Harap masukkan password').label("Password"),
        });

        return {
            login,
            submitButton,
            getAssetPath,
            store, router
        };
    },
    data() {
        return {
            data: {
                email: '',
                password: '',
            },
        }
    },
    methods: {
        submit() {
            blockBtn(this.submitButton);

            axios.post("/auth/login", { ...this.data, type: "email" }).then(res => {
                this.store.setAuth(res.data.user, res.data.token);
              const roleName = res.data.user?.role?.name;
              const target = roleName === "admin" || roleName === "hr-admin" ? "/admin/dashboard" : "/dashboard";
              this.router.push(target);
            }).catch(error => {
                toast.error(error.response.data.message);
            }).finally(() => {
                unblockBtn(this.submitButton);
            });
        },
        togglePassword(ev) {
            const type = document.querySelector("[name=password]").type;

            if (type === 'password') {
                document.querySelector("[name=password]").type = 'text';
                ev.target.classList.add("bi-eye");
                ev.target.classList.remove("bi-eye-slash");
            } else {
                document.querySelector("[name=password]").type = 'password';
                ev.target.classList.remove("bi-eye");
                ev.target.classList.add("bi-eye-slash");
            }

        }
    }
})
</script>

<style scoped>
@import url("https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap");

.auth-form {
  font-family: "Manrope", sans-serif;
}

.auth-field { margin-bottom: 20px; }
.auth-field__label-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.auth-field__label {
  display: block;
  font-size: 13px;
  font-weight: 600;
  color: #14213d;
  margin-bottom: 7px;
}
.auth-field__link {
  font-size: 12.5px;
  font-weight: 600;
  color: #2563eb;
  text-decoration: none;
}
.auth-field__wrap { position: relative; }

.auth-field__icon {
  position: absolute;
  left: 13px;
  top: 50%;
  transform: translateY(-50%);
  color: #93a5c9;
  display: grid;
  place-items: center;
  pointer-events: none;
}

.auth-form .auth-field__input {
  width: 100%;
  border: 1.4px solid #d7e2f7 !important;
  background: #ffffff !important;
  border-radius: 10px;
  padding: 12px 14px;
  font-size: 14.5px;
  font-family: "Manrope", sans-serif;
  color: #14213d !important;
  transition: border-color 0.15s ease, box-shadow 0.15s ease;
  box-shadow: none !important;
}
.auth-form .auth-field__input::placeholder { color: #93a5c9; }
.auth-form .auth-field__input:focus {
  outline: none;
  border-color: #3b82f6 !important;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.18) !important;
}
.auth-form .auth-field__input--icon { padding-left: 38px; }
.auth-form .auth-field__input--icon-right { padding-right: 42px; }

.auth-form .auth-field__input:-webkit-autofill,
.auth-form .auth-field__input:-webkit-autofill:hover,
.auth-form .auth-field__input:-webkit-autofill:focus {
  -webkit-text-fill-color: #14213d;
  -webkit-box-shadow: 0 0 0px 1000px #ffffff inset !important;
  transition: background-color 9999s ease-in-out 0s;
}

.auth-field__toggle {
  position: absolute;
  right: 6px;
  top: 50%;
  transform: translateY(-50%);
  border: none;
  background: transparent;
  color: #9ca3af;
  padding: 6px;
  cursor: pointer;
  line-height: 0;
}
.auth-field__toggle:hover { color: #14213d; }

.auth-field__error {
  font-size: 12.5px;
  color: #c0392b;
  margin-top: 6px;
  min-height: 1px;
}

.auth-submit {
  width: 100%;
  border: none;
  border-radius: 10px;
  background: #2563eb;
  color: #ffffff;
  font-size: 14.5px;
  font-weight: 700;
  padding: 13px 0;
  margin-top: 8px;
  cursor: pointer;
  transition: background 0.15s ease, transform 0.1s ease;
}
.auth-submit:hover { background: #1d4ed8; }
.auth-submit:active { transform: translateY(1px); }
</style>
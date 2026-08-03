<script setup lang="ts">
import { getAssetPath } from "@/core/helpers/assets";
import { computed } from "vue";
import { useI18n } from "vue-i18n";
import { useAuthStore } from "@/stores/auth";
import { useRouter, useRoute } from "vue-router";
import Swal from "sweetalert2/dist/sweetalert2.js";

const router = useRouter();
const route = useRoute();
const i18n = useI18n();
const store = useAuthStore();

i18n.locale.value = localStorage.getItem("lang")
  ? (localStorage.getItem("lang") as string)
  : "en";

const countries = {
  en: {
    flag: getAssetPath("media/flags/united-states.svg"),
    name: "English",
  },
  es: {
    flag: getAssetPath("media/flags/spain.svg"),
    name: "Spanish",
  },
  de: {
    flag: getAssetPath("media/flags/germany.svg"),
    name: "German",
  },
  ja: {
    flag: getAssetPath("media/flags/japan.svg"),
    name: "Japanese",
  },
  fr: {
    flag: getAssetPath("media/flags/france.svg"),
    name: "French",
  },
};

const signOut = () => {
  Swal.fire({
    icon: "warning",
    text: "Apakah Anda yakin ingin keluar?",
    showCancelButton: true,
    confirmButtonText: "Ya, Keluar",
    cancelButtonText: "Batal",
    reverseButtons: true,
    buttonsStyling: false,
    customClass: {
      confirmButton: "btn fw-semibold btn-light-primary",
      cancelButton: "btn fw-semibold btn-light-danger",
    },
  }).then(async (result) => {
    if (result.isConfirmed) {
      // Tunggu proses logout selesai (clear token/state, dan mungkin
      // redirect internal) sebelum melakukan apa pun setelahnya.
      await store.logout();

      // Hanya redirect di sini kalau store.logout() ternyata
      // belum memindahkan kita ke halaman sign-in.
      // Ini mencegah dua router.push() saling tabrakan.
      if (route.name !== "sign-in") {
        router.push({ name: "sign-in" });
      }
    }
  });
};
</script>

<template>
  <div
    class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold py-4 fs-6 w-275px"
    data-kt-menu="true"
  >
    <div class="menu-item px-3">
      <div class="menu-content d-flex align-items-center px-3">
        <div class="symbol symbol-50px me-5">
          <img
            alt="Logo"
            :src="store.user.photo || getAssetPath('media/avatars/blank.png')"
          />
        </div>
        <div class="d-flex flex-column">
          <div class="fw-bold d-flex align-items-center fs-5">
            {{ store.user.name }}
            <span class="badge badge-light-success fw-bold fs-8 px-2 py-1 ms-2">
              {{ store.user.role?.name }}
            </span>
          </div>
          <a href="#" class="fw-semibold text-muted text-hover-primary fs-7">
            {{ store.user.email }}
          </a>
        </div>
      </div>
    </div>
    <div class="separator my-2"></div>
    <div class="menu-item px-5 my-1">
      <router-link to="/dashboard/profile" class="menu-link px-5">
        Pengaturan Akun
      </router-link>
    </div>
    <div class="menu-item px-5">
      <a @click="signOut()" class="menu-link px-5"> SignOut </a>
    </div>
  </div>
</template>
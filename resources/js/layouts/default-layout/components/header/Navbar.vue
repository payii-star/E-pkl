<script setup lang="ts">
import { getAssetPath } from "@/core/helpers/assets";
import { computed, ref } from "vue";
import { storeToRefs } from "pinia";
import KTUserMenu from "@/layouts/default-layout/components/menus/UserAccountMenu.vue";
import KTThemeModeSwitcher from "@/layouts/default-layout/components/theme-mode/ThemeModeSwitcher.vue";
import { ThemeModeComponent } from "@/assets/ts/layout";
import { useThemeStore } from "@/stores/theme";
import { useAuthStore } from "@/stores/auth";
import { useTahunStore } from "@/stores/tahun";

const store = useThemeStore();

// PENTING: pakai storeToRefs, jangan destructure langsung dari
// useAuthStore(). Destructure biasa ("const { user } = useAuthStore()")
// memutus reaktivitas - perubahan user.photo/name setelah update profil
// nggak akan kelihatan di sini sampai halaman di-refresh manual.
const authStore = useAuthStore();
const { user } = storeToRefs(authStore);

const themeMode = computed(() => {
    if (store.mode === "system") {
        return ThemeModeComponent.getSystemMode();
    }
    return store.mode;
});

const tahun = useTahunStore()
const tahuns = ref<Array<Number>>([])
for (let i = new Date().getFullYear(); i >= new Date().getFullYear() - 2; i--) {
    tahuns.value.push(i)
}

// --- Avatar inisial (sama seperti di halaman Profil & UserAccountMenu) ---
const blankAvatarPath = getAssetPath("media/avatars/blank.png");

const hasRealPhoto = computed(() => {
    return !!user.value.photo && user.value.photo !== blankAvatarPath;
});

const initials = computed(() => {
    const name = user.value.name?.trim();
    if (!name) return "?";
    const parts = name.split(/\s+/).filter(Boolean);
    if (parts.length === 1) return parts[0].charAt(0).toUpperCase();
    return (parts[0].charAt(0) + parts[parts.length - 1].charAt(0)).toUpperCase();
});
</script>

<template>
    <div class="app-navbar flex-shrink-0">
        <div class="app-navbar-item ms-1 ms-md-3">
            <a href="#"
                class="btn btn-icon btn-custom btn-icon-muted btn-active-light btn-active-color-primary w-30px h-30px w-md-40px h-md-40px"
                data-kt-menu-trigger="{default:'click', lg: 'hover'}" data-kt-menu-attach="parent"
                data-kt-menu-placement="bottom-end">
                <KTIcon v-if="themeMode === 'light'" icon-name="night-day" icon-class="fs-2" />
                <KTIcon v-else icon-name="moon" icon-class="fs-2" />
            </a>
            <KTThemeModeSwitcher />
        </div>
        <div class="app-navbar-item ms-1 ms-md-4" id="kt_header_user_menu_toggle">
            <div class="cursor-pointer symbol symbol-35px" data-kt-menu-trigger="{default: 'click', lg: 'hover'}"
                data-kt-menu-attach="parent" data-kt-menu-placement="bottom-end">
                <img v-if="hasRealPhoto" :src="user.photo" class="navbar-avatar-photo rounded-circle" alt="user" />
                <div v-else class="navbar-avatar-initials rounded-circle">
                    {{ initials }}
                </div>
            </div>
            <KTUserMenu />
            </div>
        <div class="app-navbar-item d-lg-none ms-2 me-n2" v-tooltip title="Show header menu">
            <div class="btn btn-flex btn-icon btn-active-color-primary w-30px h-30px" id="kt_app_header_menu_toggle">
                <KTIcon icon-name="element-4" icon-class="fs-2" />
            </div>
        </div>
        </div>
    </template>

<style scoped>
.navbar-avatar-photo {
    width: 35px;
    height: 35px;
    object-fit: cover;
    border-radius: 50%;
}

.navbar-avatar-initials {
    width: 35px;
    height: 35px;
    border-radius: 50%;
    display: grid;
    place-items: center;
    font-size: 14px;
    font-weight: 700;
    color: #fff;
    background: var(--bs-primary, #009ef7);
    user-select: none;
}
</style>
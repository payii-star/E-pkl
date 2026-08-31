<script setup lang="ts">
import { computed } from "vue";
import { getAssetPath } from "@/core/helpers/assets";
import KTHeaderNavbar from "@/layouts/default-layout/components/header/Navbar.vue";
import {
    headerDisplay,
    headerWidthFluid,
    layout,
    themeMode,
} from "@/layouts/default-layout/config/helper";
import { useSetting } from "@/services";

const { data: setting = {} } = useSetting();
const mobileLogoFallback = "/media/logos/default-small.svg";

const resolveLogoUrl = (rawLogo?: string): string => {
    if (!rawLogo) {
        return getAssetPath(mobileLogoFallback.replace(/^\/+/, ""));
    }

    // Already a full/absolute URL (http://... or https://...) -> use as-is.
    if (/^(https?:)?\/\//i.test(rawLogo)) {
        return rawLogo;
    }

    // Relative path from API/DB, e.g. "/storage/setting/xxx.png" or "media/...".
    // Must go through getAssetPath() so the backend host (VITE_BASE_URL) is prefixed,
    // otherwise the browser requests it against the frontend's own origin and 404s.
    return getAssetPath(rawLogo.replace(/^\/+/, ""));
};

const mobileLogo = computed(() => {
    return resolveLogoUrl(setting.value?.dashboard_logo || setting.value?.logo);
});

const onMobileLogoError = (event: Event) => {
    const img = event.target as HTMLImageElement;

    if (!img || img.src.endsWith(mobileLogoFallback)) {
        return;
    }

    img.src = mobileLogoFallback;
};
</script>

<template>
    <!--begin::Header-->
    <div v-if="headerDisplay" id="kt_app_header" class="app-header">
        <!--begin::Header container-->
        <div
            class="app-container d-flex align-items-stretch justify-content-between"
            :class="{
                'container-fluid': headerWidthFluid,
                'container-xxl': !headerWidthFluid,
            }"
        >
            <div
                v-if="layout === 'light-header' || layout === 'dark-header'"
                class="d-flex align-items-center flex-grow-1 flex-lg-grow-0 me-lg-15"
            >
                <router-link to="/">
                    <img
                        v-if="
                            themeMode === 'light' && layout === 'light-header'
                        "
                        alt="Logo"
                        :src="getAssetPath('media/logos/default.svg')"
                        class="h-20px h-lg-30px app-sidebar-logo-default theme-light-show"
                    />
                    <img
                        v-if="
                            layout === 'dark-header' ||
                            (themeMode === 'dark' && layout === 'light-header')
                        "
                        alt="Logo"
                        :src="getAssetPath('media/logos/default-dark.svg')"
                        class="h-20px h-lg-30px app-sidebar-logo-default"
                    />
                </router-link>
            </div>
            <template v-else>
                <!--begin::sidebar mobile toggle-->
                <div
                    class="d-flex align-items-center d-lg-none ms-n3 me-1 me-md-2"
                    v-tooltip
                    title="Show sidebar menu"
                >
                    <div
                        class="btn btn-icon btn-active-color-primary w-35px h-35px"
                        id="kt_app_sidebar_mobile_toggle"
                    >
                        <KTIcon
                            icon-name="abstract-14"
                            icon-class="fs-2 fs-md-1"
                        />
                    </div>
                </div>
                <!--end::sidebar mobile toggle-->
                <!--begin::Mobile logo-->
                <div
                    class="d-flex align-items-center flex-grow-1 flex-lg-grow-0"
                >
                    <router-link to="/" class="d-lg-none">
                        <img
                            alt="Logo"
                            :src="mobileLogo"
                            @error="onMobileLogoError"
                            class="h-30px"
                        />
                    </router-link>
                </div>
                <!--end::Mobile logo-->
            </template>
            <!--begin::Header wrapper-->
            <div
                class="d-flex align-items-stretch justify-content-end flex-lg-grow-1"
                id="kt_app_header_wrapper"
            >
                <KTHeaderNavbar />
            </div>
            <!--end::Header wrapper-->
        </div>
        <!--end::Header container-->
    </div>
    <!--end::Header-->
</template>
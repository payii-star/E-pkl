import {
    createRouter,
    createWebHistory,
    type RouteRecordRaw,
} from "vue-router";
import { useAuthStore } from "@/stores/auth";
import { useConfigStore } from "@/stores/config";
import NProgress from "nprogress";
import "nprogress/nprogress.css";

declare module "vue-router" {
    interface RouteMeta {
        pageTitle?: string;
        permission?: string;
    }
}

const routes: Array<RouteRecordRaw> = [
    {
        path: "/",
        redirect: "/dashboard",
        component: () => import("@/layouts/default-layout/DefaultLayout.vue"),
        meta: {
            middleware: "auth",
        },
        children: [
            {
                path: "/dashboard",
                name: "dashboard",
                component: () => import("@/pages/dashboard/Dashboard.vue"),
                meta: {
                    pageTitle: "Dashboard",
                    breadcrumbs: ["Dashboard"],
                },
            },
            {
                path: "/admin/dashboard",
                name: "admin-dashboard",
                component: () => import("@/pages/admin/Dashboard.vue"),
                meta: {
                    pageTitle: "Admin Dashboard",
                    breadcrumbs: ["Admin", "Dashboard"],
                    permission: "admin-dashboard",
                },
            },
            {
                path: "/admin/face/interns",
                name: "admin-face-management",
                component: () => import("@/pages/admin/AdminFaceManagement.vue"),
                meta: {
                    pageTitle: "Face Management",
                    breadcrumbs: ["Admin", "Face"],
                    permission: "admin-dashboard",
                },
            },
            {
                path: "/admin/attendance/recap",
                name: "admin-attendance-recap",
                component: () => import("@/pages/admin/AdminAttendanceRecap.vue"),
                meta: {
                    pageTitle: "Rekap Absensi",
                    breadcrumbs: ["Admin", "Rekap Absensi"],
                    permission: "admin-attendance-recap",
                },
            },
            {
                path: "/dashboard/profile",
                name: "dashboard.profile",
                component: () => import("@/pages/dashboard/profile/Index.vue"),
                meta: {
                    pageTitle: "Profile",
                    breadcrumbs: ["Profile"],
                },
            },
            {
                path: "/dashboard/setting",
                name: "dashboard.setting",
                component: () => import("@/pages/dashboard/setting/Index.vue"),
                meta: {
                    pageTitle: "Website Setting",
                    breadcrumbs: ["Website", "Setting"],
                },
            },

            // MASTER (User & Role management)
            {
                path: "/dashboard/master/users",
                name: "dashboard.master.users",
                component: () =>
                    import("@/pages/dashboard/master/users/Index.vue"),
                meta: {
                    pageTitle: "Users",
                    breadcrumbs: ["Master", "Users"],
                },
            },
            {
                path: "/dashboard/master/users/roles",
                name: "dashboard.master.users.roles",
                component: () =>
                    import("@/pages/dashboard/master/users/roles/Index.vue"),
                meta: {
                    pageTitle: "User Roles",
                    breadcrumbs: ["Master", "Users", "Roles"],
                },
            },

            // LANDING CMS (dashboard baru, terpisah dari Master)
            {
                path: "/dashboard/landing-cms",
                name: "dashboard.landing-cms",
                component: () => import("@/pages/dashboard/landing-cms/Index.vue"),
                meta: {
                    pageTitle: "Landing CMS",
                    breadcrumbs: ["Landing CMS"],
                    permission: "landing-cms",
                },
            },
            {
                path: "/dashboard/landing-cms/clients",
                name: "dashboard.landing-cms.clients",
                component: () => import("@/pages/dashboard/landing-cms/clients/Index.vue"),
                meta: {
                    pageTitle: "Client Logos",
                    breadcrumbs: ["Landing CMS", "Client Logos"],
                    permission: "landing-cms",
                },
            },
            {
                path: "/dashboard/landing-cms/menu",
                name: "dashboard.landing-cms.menu",
                component: () => import("@/pages/dashboard/landing-cms/menu/Index.vue"),
                meta: {
                    pageTitle: "Navbar Menu",
                    breadcrumbs: ["Landing CMS", "Menu"],
                    permission: "landing-cms",
                },
            },
            {
                path: "/dashboard/landing-cms/services",
                name: "dashboard.landing-cms.services",
                component: () => import("@/pages/dashboard/landing-cms/services/Index.vue"),
                meta: {
                    pageTitle: "Layanan",
                    breadcrumbs: ["Landing CMS", "Layanan"],
                    permission: "landing-cms",
                },
            },
            {
                path: "/dashboard/landing-cms/statistics",
                name: "dashboard.landing-cms.statistics",
                component: () => import("@/pages/dashboard/landing-cms/statistics/Index.vue"),
                meta: {
                    pageTitle: "Statistik",
                    breadcrumbs: ["Landing CMS", "Statistik"],
                    permission: "landing-cms",
                },
            },
            {
                path: "/dashboard/landing-cms/teams",
                name: "dashboard.landing-cms.teams",
                component: () => import("@/pages/dashboard/landing-cms/teams/Index.vue"),
                meta: {
                    pageTitle: "Tim",
                    breadcrumbs: ["Landing CMS", "Tim"],
                    permission: "landing-cms",
                },
            },
            {
                path: "/dashboard/landing-cms/testimonials",
                name: "dashboard.landing-cms.testimonials",
                component: () => import("@/pages/dashboard/landing-cms/testimonials/Index.vue"),
                meta: {
                    pageTitle: "Testimoni",
                    breadcrumbs: ["Landing CMS", "Testimoni"],
                    permission: "landing-cms",
                },
            },
            {
                path: "/dashboard/landing-cms/contact-messages",
                name: "dashboard.landing-cms.contact-messages",
                component: () => import("@/pages/dashboard/landing-cms/contact-messages/Index.vue"),
                meta: {
                    pageTitle: "Pesan Masuk",
                    breadcrumbs: ["Landing CMS", "Pesan Masuk"],
                    permission: "landing-cms",
                },
            },
            {
                path: "/dashboard/landing-cms/footer",
                name: "dashboard.landing-cms.footer",
                component: () => import("@/pages/dashboard/landing-cms/footer/Index.vue"),
                meta: {
                    pageTitle: "Footer",
                    breadcrumbs: ["Landing CMS", "Footer"],
                    permission: "landing-cms",
                },
            },
            {
                path: "/dashboard/landing-cms/content",
                name: "dashboard.landing-cms.content",
                component: () => import("@/pages/dashboard/landing-cms/landing-content/Index.vue"),
                meta: {
                    pageTitle: "Konten Landing",
                    breadcrumbs: ["Landing CMS", "Konten"],
                    permission: "landing-cms",
                },
            },
            {
                path: "/dashboard/landing-cms/projects",
                name: "dashboard.landing-cms.projects",
                component: () => import("@/pages/dashboard/landing-cms/projects/Index.vue"),
                meta: {
                    pageTitle: "Proyek",
                    breadcrumbs: ["Landing CMS", "Proyek"],
                    permission: "landing-cms",
                },
            },

            // ABSENSI (baru - struktur awal)
            {
                path: "/attendance/check",
                name: "attendance-check",
                component: () =>
                    import("@/pages/attendance/Checkout.vue"),
                meta: {
                    pageTitle: "Absen Sekarang",
                    breadcrumbs: ["Absensi", "Absen"],
                },
            },
            {
                path: "/attendance/history",
                name: "attendance-history",
                component: () =>
                    import("@/pages/attendance/History.vue"),
                meta: {
                    pageTitle: "Riwayat Absensi",
                    breadcrumbs: ["Absensi", "Riwayat"],
                },
            },

            // JURNAL HARIAN (baru - struktur awal)
            {
                path: "/journal/my",
                name: "journal-my",
                component: () => import("@/pages/journal/MyJournal.vue"),
                meta: {
                    pageTitle: "Jurnal Saya",
                    breadcrumbs: ["Jurnal", "Jurnal Saya"],
                },
            },
            {
                path: "/journal/history",
                name: "journal-history",
                component: () => import("@/pages/journal/History.vue"),
                meta: {
                    pageTitle: "Riwayat Jurnal",
                    breadcrumbs: ["Jurnal", "Riwayat"],
                },
            },
            {
                path: "/journal/approval",
                name: "journal-approval",
                component: () => import("@/pages/journal/Approval.vue"),
                alias: "/admin/journals/approval",
                meta: {
                    pageTitle: "Approval Jurnal",
                    breadcrumbs: ["Jurnal", "Approval"],
                    permission: "journal-approval",
                },
            },
        ],
    },
    {
        path: "/",
        component: () => import("@/layouts/AuthLayout.vue"),
        children: [
            {
                path: "/sign-in",
                name: "sign-in",
                component: () => import("@/pages/auth/sign-in/Index.vue"),
                meta: {
                    pageTitle: "Sign In",
                    middleware: "guest",
                },
            },
            {
                path: "/face-login",
                name: "face-login",
                component: () => import("@/pages/auth/face-login/Index.vue"),
                meta: {
                    pageTitle: "Login dengan Wajah",
                    middleware: "guest",
                },
            },
            {
                path: "/sign-up",
                name: "sign-up",
                component: () => import("@/pages/auth/sign-up/Index.vue"),
                meta: {
                    pageTitle: "Daftar Akun",
                    middleware: "guest",
                },
            },
        ],
    },
    {
        path: "/",
        component: () => import("@/layouts/SystemLayout.vue"),
        children: [
            {
                // the 404 route, when none of the above matches
                path: "/404",
                name: "404",
                component: () => import("@/pages/errors/Error404.vue"),
                meta: {
                    pageTitle: "Error 404",
                },
            },
            {
                path: "/500",
                name: "500",
                component: () => import("@/pages/errors/Error500.vue"),
                meta: {
                    pageTitle: "Error 500",
                },
            },
        ],
    },
    {
        path: "/:pathMatch(.*)*",
        redirect: "/404",
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
    scrollBehavior(to) {
        // If the route has a hash, scroll to the section with the specified ID; otherwise, scroll to the top of the page.
        if (to.hash) {
            return {
                el: to.hash,
                top: 80,
                behavior: "smooth",
            };
        } else {
            return {
                top: 0,
                left: 0,
                behavior: "smooth",
            };
        }
    },
});

router.beforeEach(async (to, from, next) => {
    if (to.name) {
        // Start the route progress bar.
        NProgress.start();
    }

    const authStore = useAuthStore();
    const configStore = useConfigStore();

    // current page view title
    if (to.meta.pageTitle) {
        document.title = `${to.meta.pageTitle} - ${import.meta.env.VITE_APP_NAME
            }`;
    } else {
        document.title = import.meta.env.VITE_APP_NAME as string;
    }

    // reset config to initial state
    configStore.resetLayoutConfig();

    const isAdminUser = () => {
        const roleName = authStore.user?.role?.name;
        return roleName === "admin" || roleName === "hr-admin";
    };

    // verify auth token before each page change
    if (!authStore.isAuthenticated) await authStore.verifyAuth();

    // before page access check if page requires authentication
    if (to.meta.middleware == "auth") {
        if (authStore.isAuthenticated) {
            if (to.meta.permission && isAdminUser()) {
                return next();
            }

            if (
                to.meta.permission &&
                !authStore.user.permission.includes(to.meta.permission)
            ) {
                return next({ name: "404" });
            }

            if (to.meta.checkDetail == false) {
                return next();
            }

            return next();
        }
        return next({ name: "sign-in" });
    }

    if (to.meta.middleware == "guest" && authStore.isAuthenticated) {
        const target = isAdminUser() ? "admin-dashboard" : "dashboard";
        return next({ name: target });
    }

    next();
});

router.afterEach(() => {
    // Complete the animation of the route progress bar.
    NProgress.done();
});

export default router;
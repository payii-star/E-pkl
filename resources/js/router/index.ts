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
        roles?: string[];
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
                path: "/admin/tasks",
                name: "admin-tasks",
                component: () => import("@/pages/admin/AdminTasks.vue"),
                meta: {
                    pageTitle: "Kelola Tugas",
                    breadcrumbs: ["Admin", "Kelola Tugas"],
                    permission: "task-management",
                },
            },
            {
                path: "/admin/leave-requests",
                name: "admin-leave-requests",
                component: () => import("@/pages/admin/AdminLeaveRequests.vue"),
                meta: {
                    pageTitle: "Kelola Izin",
                    breadcrumbs: ["Admin", "Kelola Izin"],
                    permission: "leave-management",
                },
            },

                {
                    path: "/admin/intern-periods",
                    name: "admin-intern-periods",
                    component: () => import("@/pages/admin/Admininternperiods.vue"),
                    meta: {
                        pageTitle: "Periode Magang",
                        breadcrumbs: ["Admin", "Periode Magang"],
                        permission: "admin-dashboard",
                    },
                },

            // KELOLA LANDING (dipindahkan dari dashboard Landing)
            {
                path: "/admin/landing/projects",
                name: "landing-projects",
                component: () => import("@/pages/dashboard/landing-cms/projects/Index.vue"),
                meta: {
                    pageTitle: "Projects",
                    breadcrumbs: ["Kelola Landing", "Projects"],
                    permission: "landing-management",
                },
            },
            {
                path: "/admin/landing/statistics",
                name: "landing-statistics",
                component: () => import("@/pages/dashboard/landing-cms/statistics/Index.vue"),
                meta: {
                    pageTitle: "Statistics",
                    breadcrumbs: ["Kelola Landing", "Statistics"],
                    permission: "landing-management",
                },
            },
            {
                path: "/admin/landing/menu",
                name: "landing-menu",
                component: () => import("@/pages/dashboard/landing-cms/menu/Index.vue"),
                meta: {
                    pageTitle: "Menu",
                    breadcrumbs: ["Kelola Landing", "Menu"],
                    permission: "landing-management",
                },
            },
            {
                path: "/admin/landing/services",
                name: "landing-services",
                component: () => import("@/pages/dashboard/landing-cms/services/Index.vue"),
                meta: {
                    pageTitle: "Services",
                    breadcrumbs: ["Kelola Landing", "Services"],
                    permission: "landing-management",
                },
            },
            {
                path: "/admin/landing/testimonials",
                name: "landing-testimonials",
                component: () => import("@/pages/dashboard/landing-cms/testimonials/Index.vue"),
                meta: {
                    pageTitle: "Testimonials",
                    breadcrumbs: ["Kelola Landing", "Testimonials"],
                    permission: "landing-management",
                },
            },
            {
                path: "/admin/landing/teams",
                name: "landing-teams",
                component: () => import("@/pages/dashboard/landing-cms/teams/Index.vue"),
                meta: {
                    pageTitle: "Teams",
                    breadcrumbs: ["Kelola Landing", "Teams"],
                    permission: "landing-management",
                },
            },
            {
                path: "/admin/landing/footer",
                name: "landing-footer",
                component: () => import("@/pages/dashboard/landing-cms/footer/Index.vue"),
                meta: {
                    pageTitle: "Footer",
                    breadcrumbs: ["Kelola Landing", "Footer"],
                    permission: "landing-management",
                },
            },
            {
                path: "/admin/landing/content",
                name: "landing-content",
                component: () => import("@/pages/dashboard/landing-cms/landing-content/Index.vue"),
                meta: {
                    pageTitle: "Landing Content",
                    breadcrumbs: ["Kelola Landing", "Landing Content"],
                    permission: "landing-management",
                },
            },
            {
                path: "/admin/landing/clients",
                name: "landing-clients",
                component: () => import("@/pages/dashboard/landing-cms/clients/Index.vue"),
                meta: {
                    pageTitle: "Clients",
                    breadcrumbs: ["Kelola Landing", "Clients"],
                    permission: "landing-management",
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

            // ABSENSI (baru - struktur awal)
            {
                path: "/attendance/check-in",
                name: "attendance-check-in",
                component: () =>
                    import("@/pages/attendance/CheckIn.vue"),
                meta: {
                    pageTitle: "Check In",
                    breadcrumbs: ["Absensi", "Check In"],
                    roles: ["karyawan"],
                },
            },
            {
                path: "/attendance/check-out",
                name: "attendance-check-out",
                component: () =>
                    import("@/pages/attendance/CheckOut.vue"),
                meta: {
                    pageTitle: "Check Out",
                    breadcrumbs: ["Absensi", "Check Out"],
                    roles: ["karyawan"],
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

            // TUGAS (dari hr-admin ke intern)
            {
                path: "/tasks/my",
                name: "tasks-my",
                component: () => import("@/pages/task/MyTasks.vue"),
                meta: {
                    pageTitle: "Tugas Saya",
                    breadcrumbs: ["Tugas", "Tugas Saya"],
                },
            },

            // IZIN TIDAK MASUK
            {
                path: "/leave/my",
                name: "leave-my",
                component: () => import("@/pages/leave/MyLeaveRequests.vue"),
                meta: {
                    pageTitle: "Izin Tidak Masuk",
                    breadcrumbs: ["Izin", "Izin Tidak Masuk"],
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

    const hasRequiredRole = (roles: string[] = []) => {
        if (!roles.length) return true;
        const roleName = authStore.user?.role?.name;
        return !!roleName && roles.includes(roleName);
    };

    // verify auth token before each page change
    if (!authStore.isAuthenticated) await authStore.verifyAuth();

    // before page access check if page requires authentication
    if (to.meta.middleware == "auth") {
        if (authStore.isAuthenticated) {
            if (to.meta.roles && !hasRequiredRole(to.meta.roles)) {
                return next({ name: "404" });
            }

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
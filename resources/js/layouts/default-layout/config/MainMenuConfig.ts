import type { MenuItem } from "@/layouts/default-layout/config/types";

const MainMenuConfig: Array<MenuItem> = [
    {
        pages: [
            {
                heading: "Dashboard",
                name: "dashboard",
                route: "/dashboard",
                keenthemesIcon: "element-11",
                hideForAdmin: true,
            },
            {
                heading: "Admin Dashboard",
                name: "admin-dashboard",
                route: "/admin/dashboard",
                keenthemesIcon: "chart-simple",
                permission: "admin-dashboard",
            },
            {
                heading: "Face Management",
                name: "admin-face-management",
                route: "/admin/face/interns",
                keenthemesIcon: "user",
                permission: "admin-dashboard",
            },
            {
                heading: "Rekap Absensi",
                name: "admin-attendance-recap",
                route: "/admin/attendance/recap",
                keenthemesIcon: "calendar-tick",
                permission: "admin-attendance-recap",
            },
            {
                heading: "Kelola Tugas",
                name: "admin-tasks",
                route: "/admin/tasks",
                keenthemesIcon: "clipboard",
                permission: "task-management",
            },
            {
                heading: "Kelola Izin",
                name: "admin-leave-requests",
                route: "/admin/leave-requests",
                keenthemesIcon: "calendar-remove",
                permission: "leave-management",
            },
                {
                    heading: "Periode Magang",
                    route: "/admin/intern-periods",
                    name: "admin-intern-periods",
                    keenthemesIcon: "calendar",
                    permission: "admin-dashboard",
                },
            {
                heading: "Approval Jurnal",
                route: "/journal/approval",
                name: "journal-approval",
                keenthemesIcon: "check-circle",
                permission: "journal-approval",
            },
        ],
    },

    // ABSENSI
    {
        heading: "Absensi",
        route: "/attendance",
        pages: [
            {
                heading: "Absen Pulang",
                route: "/attendance/check",
                name: "attendance-check",
                keenthemesIcon: "geolocation",
                hideForAdmin: true,
            },
            {
                heading: "Riwayat Absensi",
                route: "/attendance/history",
                name: "attendance-history",
                keenthemesIcon: "calendar",
                hideForAdmin: true,
            },
        ],
    },

    // JURNAL
    {
        heading: "Jurnal Harian",
        route: "/journal",
        pages: [
            {
                heading: "Jurnal Saya",
                route: "/journal/my",
                name: "journal-my",
                keenthemesIcon: "notepad",
                hideForAdmin: true,
            },
            {
                heading: "Riwayat Jurnal",
                route: "/journal/history",
                name: "journal-history",
                keenthemesIcon: "document",
                hideForAdmin: true,
            },
        ],
    },

    // TUGAS (diberikan hr-admin ke intern)
    {
        heading: "Tugas",
        route: "/tasks",
        pages: [
            {
                heading: "Tugas Saya",
                route: "/tasks/my",
                name: "tasks-my",
                keenthemesIcon: "clipboard",
                hideForAdmin: true,
            },
        ],
    },

    // IZIN TIDAK MASUK
    {
        heading: "Izin",
        route: "/leave",
        pages: [
            {
                heading: "Izin Tidak Masuk",
                route: "/leave/my",
                name: "leave-my",
                keenthemesIcon: "calendar-remove",
                hideForAdmin: true,
            },
        ],
    },

    // MASTER (user & role management)
    {
        heading: "Master",
        route: "/master",
        pages: [
            {
                sectionTitle: "User & Role",
                route: "/users",
                keenthemesIcon: "people",
                name: "master-user",
                permission: "master-user",
                sub: [
                    {
                        heading: "Role",
                        name: "master-role",
                        route: "/dashboard/master/users/roles",
                        permission: "master-role",
                    },
                    {
                        heading: "User / Karyawan",
                        name: "master-user",
                        route: "/dashboard/master/users",
                        permission: "master-user",
                    },
                ],
            },
        ],
    },

    // KELOLA LANDING (dashboard admin landing — berdiri sendiri, bukan bagian dari Master)
    {
        heading: "Kelola Landing",
        route: "/admin/landing",
        pages: [
            {
                heading: "Projects",
                route: "/admin/landing/projects",
                name: "landing-projects",
                keenthemesIcon: "briefcase",
                permission: "landing-management",
            },
            {
                heading: "Statistics",
                route: "/admin/landing/statistics",
                name: "landing-statistics",
                keenthemesIcon: "chart-simple",
                permission: "landing-management",
            },
            {
                heading: "Menu",
                route: "/admin/landing/menu",
                name: "landing-menu",
                keenthemesIcon: "burger-menu",
                permission: "landing-management",
            },
            {
                heading: "Services",
                route: "/admin/landing/services",
                name: "landing-services",
                keenthemesIcon: "gear",
                permission: "landing-management",
            },
            {
                heading: "Testimonials",
                route: "/admin/landing/testimonials",
                name: "landing-testimonials",
                keenthemesIcon: "message-text-2",
                permission: "landing-management",
            },
            {
                heading: "Teams",
                route: "/admin/landing/teams",
                name: "landing-teams",
                keenthemesIcon: "people",
                permission: "landing-management",
            },
            {
                heading: "Footer",
                route: "/admin/landing/footer",
                name: "landing-footer",
                keenthemesIcon: "row-horizontal",
                permission: "landing-management",
            },
            {
                heading: "Landing Content",
                route: "/admin/landing/content",
                name: "landing-content",
                keenthemesIcon: "document",
                permission: "landing-management",
            },
            {
                heading: "Client",
                route: "/admin/landing/clients",
                name: "landing-clients",
                keenthemesIcon: "people",
                permission: "landing-management",
            },
        ],
    },

    // AKUN
    {
        heading: "Akun",
        route: "/account",
        pages: [
            {
                heading: "Profil Saya",
                route: "/dashboard/profile",
                name: "dashboard-profile",
                keenthemesIcon: "profile-circle",
            },
            {
                heading: "Setting",
                route: "/dashboard/setting",
                name: "setting",
                keenthemesIcon: "setting-2",
            },
        ],
    },
];

export default MainMenuConfig;
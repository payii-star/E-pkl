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
        ],
    },

    // ABSENSI
    {
        heading: "Absensi",
        route: "/attendance",
        pages: [
            {
                heading: "Absen Sekarang",
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
            {
                // TODO: idealnya item ini hanya tampil untuk role Atasan/Supervisor
                // (butuh helper cek permission di SidebarMenu, dikerjakan setelah role HR final)
                heading: "Approval Jurnal",
                route: "/journal/approval",
                name: "journal-approval",
                keenthemesIcon: "check-circle",
                permission: "journal-approval",
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
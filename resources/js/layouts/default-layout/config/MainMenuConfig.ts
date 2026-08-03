import type { MenuItem } from "@/layouts/default-layout/config/types";

const MainMenuConfig: Array<MenuItem> = [
    {
        pages: [
            {
                heading: "Dashboard",
                name: "dashboard",
                route: "/dashboard",
                keenthemesIcon: "element-11",
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
            },
            {
                heading: "Riwayat Absensi",
                route: "/attendance/history",
                name: "attendance-history",
                keenthemesIcon: "calendar",
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
            },
            {
                heading: "Riwayat Jurnal",
                route: "/journal/history",
                name: "journal-history",
                keenthemesIcon: "document",
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
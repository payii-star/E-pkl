import type { MenuItem } from "@/layouts/default-layout/config/types";

const MainMenuConfig: Array<MenuItem> = [
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
        ],
    },

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
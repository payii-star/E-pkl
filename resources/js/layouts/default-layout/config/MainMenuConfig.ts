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

    // WEBSITE
    {
        heading: "Website",
        route: "/dashboard/website",
        name: "website",
        pages: [
            // MASTER
            {
                sectionTitle: "Master",
                route: "/master",
                keenthemesIcon: "cube-3",
                name: "master",
                sub: [
                    {
                        sectionTitle: "User",
                        route: "/users",
                        name: "master-user",
                        sub: [
                            {
                                heading: "Role",
                                name: "master-role",
                                route: "/dashboard/master/users/roles",
                            },
                            {
                                heading: "User",
                                name: "master-user",
                                route: "/dashboard/master/users",
                            },
                        ],
                    },
                    {
                        heading: "Products",
                        name: "master-products", // Samakan dengan name di router
                        route: "/dashboard/master/products", // Samakan dengan path di router
                        permission: "master-user",
                    },
                    {
                        heading: "Categories",
                        name: "master-category", // Cocokkan dengan nama izin
                        route: "/dashboard/master/categories",
                    },
                    {
                        heading: "Variants",
                        name: "master-variant", // Cocokkan dengan nama izin
                        route: "/dashboard/master/variants",
                    },
                    {
                        heading: "Promo",
                        name: "master-promo", // Cocokkan dengan nama izin
                        route: "/dashboard/master/promos",
                    },
                    {
                        heading: "Members",
                        name: "master-members",
                        route: "/dashboard/master/members",
                    },
                    {
                        heading: "Points",
                        name: "master-point",
                        route: "/dashboard/master/points",
                    },
                ],
            },
        ],
    },
    {
        heading: "Aplikasi",
        route: "/apps",
        pages: [
            {
                heading: "Cashier",
                route: "/apps/pos",
                name: "apps-pos-cashier", // cocokkan dengan nama izin
                keenthemesIcon: "shop",
            },
        ],
    },
    {
        heading: "Inventory",
        route: "/inventory",
        pages: [
            {
                heading: "Stock Management",
                route: "/inventory/stock",
                name: "inventory-stock", // Cocokkan dengan nama izin
                keenthemesIcon: "package",
            },
        ],
    },
    {
        heading: "Transactions",
        route: "/transactions",
        pages: [
            {
                heading: "History",
                route: "/transactions/history",
                name: "transaction-history", // Cocokkan dengan nama izin
                keenthemesIcon: "scroll",
            },
        ],
    },
    {
        heading: "Inventory",
        route: "/stock",
        pages: [
            {
                heading: "Stock History",
                route: "/stock/history",
                name: "stock-history",
                keenthemesIcon: "book",
            },
        ],
    },
    {
        heading: "Reports",
        route: "/reports",
        pages: [
            {
                heading: "Sales Report",
                route: "/reports/sales",
                name: "view-reports", // Cocokkan dengan nama izin
                keenthemesIcon: "chart-line",
            },
        ],
    },
    {
        heading: "Setting",
        route: "/setting",
        pages: [
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

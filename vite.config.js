import { fileURLToPath, URL } from "node:url";

import process from "node:process";
import { defineConfig, loadEnv } from "vite";
import vue from "@vitejs/plugin-vue";
import laravel from "laravel-vite-plugin";

// https://vitejs.dev/config/
export default defineConfig(({ mode }) => {
    process.env = { ...process.env, ...loadEnv(mode, process.cwd()) };

    return {
        server: {
            host: "0.0.0.0", // biar Vite listen di semua interface, bukan cuma IP statis
            port: 5173,
            strictPort: true,
            cors: true,               // izinkan cross-origin request ke dev server
            origin: `http://${process.env.VITE_HOST}:5173`, // dipakai Laravel utk generate URL asset
            hmr: {
                host: process.env.VITE_HOST, // biar websocket HMR juga tetap connect ke IP yang benar
            },
        },
        plugins: [
            laravel({
                input: ["resources/css/app.css", "resources/js/main.ts"],
                refresh: true,
            }),
            vue({
                template: {
                    transformAssetUrls: {
                        base: null,
                        includeAbsolute: false,
                    },
                },
            }),
        ],
        resolve: {
            alias: {
                "vue-i18n": "vue-i18n/dist/vue-i18n.cjs.js",
                "@": fileURLToPath(new URL("./resources/js", import.meta.url)),
            },
        },
        optimizeDeps: {
            esbuildOptions: {
                target: ["es2020", "safari14"],
            },
        },
        build: {
            chunkSizeWarningLimit: 3000,
            target: ["es2020", "safari14"],
            rollupOptions: {
                output: {
                    // expose jQuery as a global variable
                    globals: {
                        jquery: "jQuery",
                    },
                },
            },
        },
    };
});
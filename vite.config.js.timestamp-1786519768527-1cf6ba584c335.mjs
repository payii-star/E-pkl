// vite.config.js
import { fileURLToPath, URL } from "node:url";
import process from "node:process";
import { defineConfig, loadEnv } from "file:///C:/xampp/htdocs/E-pkl/node_modules/vite/dist/node/index.js";
import vue from "file:///C:/xampp/htdocs/E-pkl/node_modules/@vitejs/plugin-vue/dist/index.mjs";
import laravel from "file:///C:/xampp/htdocs/E-pkl/node_modules/laravel-vite-plugin/dist/index.js";
var __vite_injected_original_import_meta_url = "file:///C:/xampp/htdocs/E-pkl/vite.config.js";
var vite_config_default = defineConfig(({ mode }) => {
  process.env = { ...process.env, ...loadEnv(mode, process.cwd()) };
  return {
    server: {
      host: process.env.VITE_HOST
    },
    plugins: [
      laravel({
        input: ["resources/css/app.css", "resources/js/main.ts"],
        refresh: true
      }),
      vue({
        template: {
          transformAssetUrls: {
            base: null,
            includeAbsolute: false
          }
        }
      })
    ],
    resolve: {
      alias: {
        "vue-i18n": "vue-i18n/dist/vue-i18n.cjs.js",
        "@": fileURLToPath(new URL("./resources/js", __vite_injected_original_import_meta_url))
      }
    },
    optimizeDeps: {
      esbuildOptions: {
        target: ["es2020", "safari14"]
      }
    },
    build: {
      chunkSizeWarningLimit: 3e3,
      target: ["es2020", "safari14"],
      rollupOptions: {
        output: {
          // expose jQuery as a global variable
          globals: {
            jquery: "jQuery"
          }
        }
      }
    }
  };
});
export {
  vite_config_default as default
};
//# sourceMappingURL=data:application/json;base64,ewogICJ2ZXJzaW9uIjogMywKICAic291cmNlcyI6IFsidml0ZS5jb25maWcuanMiXSwKICAic291cmNlc0NvbnRlbnQiOiBbImNvbnN0IF9fdml0ZV9pbmplY3RlZF9vcmlnaW5hbF9kaXJuYW1lID0gXCJDOlxcXFx4YW1wcFxcXFxodGRvY3NcXFxcRS1wa2xcIjtjb25zdCBfX3ZpdGVfaW5qZWN0ZWRfb3JpZ2luYWxfZmlsZW5hbWUgPSBcIkM6XFxcXHhhbXBwXFxcXGh0ZG9jc1xcXFxFLXBrbFxcXFx2aXRlLmNvbmZpZy5qc1wiO2NvbnN0IF9fdml0ZV9pbmplY3RlZF9vcmlnaW5hbF9pbXBvcnRfbWV0YV91cmwgPSBcImZpbGU6Ly8vQzoveGFtcHAvaHRkb2NzL0UtcGtsL3ZpdGUuY29uZmlnLmpzXCI7aW1wb3J0IHsgZmlsZVVSTFRvUGF0aCwgVVJMIH0gZnJvbSBcIm5vZGU6dXJsXCI7XG5cbmltcG9ydCBwcm9jZXNzIGZyb20gXCJub2RlOnByb2Nlc3NcIjtcbmltcG9ydCB7IGRlZmluZUNvbmZpZywgbG9hZEVudiB9IGZyb20gXCJ2aXRlXCI7XG5pbXBvcnQgdnVlIGZyb20gXCJAdml0ZWpzL3BsdWdpbi12dWVcIjtcbmltcG9ydCBsYXJhdmVsIGZyb20gXCJsYXJhdmVsLXZpdGUtcGx1Z2luXCI7XG5cbi8vIGh0dHBzOi8vdml0ZWpzLmRldi9jb25maWcvXG5leHBvcnQgZGVmYXVsdCBkZWZpbmVDb25maWcoKHsgbW9kZSB9KSA9PiB7XG4gICAgcHJvY2Vzcy5lbnYgPSB7IC4uLnByb2Nlc3MuZW52LCAuLi5sb2FkRW52KG1vZGUsIHByb2Nlc3MuY3dkKCkpIH07XG5cbiAgICByZXR1cm4ge1xuICAgICAgICBzZXJ2ZXI6IHtcbiAgICAgICAgICAgIGhvc3Q6IHByb2Nlc3MuZW52LlZJVEVfSE9TVCxcbiAgICAgICAgfSxcbiAgICAgICAgcGx1Z2luczogW1xuICAgICAgICAgICAgbGFyYXZlbCh7XG4gICAgICAgICAgICAgICAgaW5wdXQ6IFtcInJlc291cmNlcy9jc3MvYXBwLmNzc1wiLCBcInJlc291cmNlcy9qcy9tYWluLnRzXCJdLFxuICAgICAgICAgICAgICAgIHJlZnJlc2g6IHRydWUsXG4gICAgICAgICAgICB9KSxcbiAgICAgICAgICAgIHZ1ZSh7XG4gICAgICAgICAgICAgICAgdGVtcGxhdGU6IHtcbiAgICAgICAgICAgICAgICAgICAgdHJhbnNmb3JtQXNzZXRVcmxzOiB7XG4gICAgICAgICAgICAgICAgICAgICAgICBiYXNlOiBudWxsLFxuICAgICAgICAgICAgICAgICAgICAgICAgaW5jbHVkZUFic29sdXRlOiBmYWxzZSxcbiAgICAgICAgICAgICAgICAgICAgfSxcbiAgICAgICAgICAgICAgICB9LFxuICAgICAgICAgICAgfSksXG4gICAgICAgIF0sXG4gICAgICAgIHJlc29sdmU6IHtcbiAgICAgICAgICAgIGFsaWFzOiB7XG4gICAgICAgICAgICAgICAgXCJ2dWUtaTE4blwiOiBcInZ1ZS1pMThuL2Rpc3QvdnVlLWkxOG4uY2pzLmpzXCIsXG4gICAgICAgICAgICAgICAgXCJAXCI6IGZpbGVVUkxUb1BhdGgobmV3IFVSTChcIi4vcmVzb3VyY2VzL2pzXCIsIGltcG9ydC5tZXRhLnVybCkpLFxuICAgICAgICAgICAgfSxcbiAgICAgICAgfSxcbiAgICAgICAgb3B0aW1pemVEZXBzOiB7XG4gICAgICAgICAgICBlc2J1aWxkT3B0aW9uczoge1xuICAgICAgICAgICAgICAgIHRhcmdldDogW1wiZXMyMDIwXCIsIFwic2FmYXJpMTRcIl0sXG4gICAgICAgICAgICB9LFxuICAgICAgICB9LFxuICAgICAgICBidWlsZDoge1xuICAgICAgICAgICAgY2h1bmtTaXplV2FybmluZ0xpbWl0OiAzMDAwLFxuICAgICAgICAgICAgdGFyZ2V0OiBbXCJlczIwMjBcIiwgXCJzYWZhcmkxNFwiXSxcbiAgICAgICAgICAgIHJvbGx1cE9wdGlvbnM6IHtcbiAgICAgICAgICAgICAgICBvdXRwdXQ6IHtcbiAgICAgICAgICAgICAgICAgICAgLy8gZXhwb3NlIGpRdWVyeSBhcyBhIGdsb2JhbCB2YXJpYWJsZVxuICAgICAgICAgICAgICAgICAgICBnbG9iYWxzOiB7XG4gICAgICAgICAgICAgICAgICAgICAgICBqcXVlcnk6IFwialF1ZXJ5XCIsXG4gICAgICAgICAgICAgICAgICAgIH0sXG4gICAgICAgICAgICAgICAgfSxcbiAgICAgICAgICAgIH0sXG4gICAgICAgIH0sXG4gICAgfTtcbn0pO1xuIl0sCiAgIm1hcHBpbmdzIjogIjtBQUF5UCxTQUFTLGVBQWUsV0FBVztBQUU1UixPQUFPLGFBQWE7QUFDcEIsU0FBUyxjQUFjLGVBQWU7QUFDdEMsT0FBTyxTQUFTO0FBQ2hCLE9BQU8sYUFBYTtBQUxxSSxJQUFNLDJDQUEyQztBQVExTSxJQUFPLHNCQUFRLGFBQWEsQ0FBQyxFQUFFLEtBQUssTUFBTTtBQUN0QyxVQUFRLE1BQU0sRUFBRSxHQUFHLFFBQVEsS0FBSyxHQUFHLFFBQVEsTUFBTSxRQUFRLElBQUksQ0FBQyxFQUFFO0FBRWhFLFNBQU87QUFBQSxJQUNILFFBQVE7QUFBQSxNQUNKLE1BQU0sUUFBUSxJQUFJO0FBQUEsSUFDdEI7QUFBQSxJQUNBLFNBQVM7QUFBQSxNQUNMLFFBQVE7QUFBQSxRQUNKLE9BQU8sQ0FBQyx5QkFBeUIsc0JBQXNCO0FBQUEsUUFDdkQsU0FBUztBQUFBLE1BQ2IsQ0FBQztBQUFBLE1BQ0QsSUFBSTtBQUFBLFFBQ0EsVUFBVTtBQUFBLFVBQ04sb0JBQW9CO0FBQUEsWUFDaEIsTUFBTTtBQUFBLFlBQ04saUJBQWlCO0FBQUEsVUFDckI7QUFBQSxRQUNKO0FBQUEsTUFDSixDQUFDO0FBQUEsSUFDTDtBQUFBLElBQ0EsU0FBUztBQUFBLE1BQ0wsT0FBTztBQUFBLFFBQ0gsWUFBWTtBQUFBLFFBQ1osS0FBSyxjQUFjLElBQUksSUFBSSxrQkFBa0Isd0NBQWUsQ0FBQztBQUFBLE1BQ2pFO0FBQUEsSUFDSjtBQUFBLElBQ0EsY0FBYztBQUFBLE1BQ1YsZ0JBQWdCO0FBQUEsUUFDWixRQUFRLENBQUMsVUFBVSxVQUFVO0FBQUEsTUFDakM7QUFBQSxJQUNKO0FBQUEsSUFDQSxPQUFPO0FBQUEsTUFDSCx1QkFBdUI7QUFBQSxNQUN2QixRQUFRLENBQUMsVUFBVSxVQUFVO0FBQUEsTUFDN0IsZUFBZTtBQUFBLFFBQ1gsUUFBUTtBQUFBO0FBQUEsVUFFSixTQUFTO0FBQUEsWUFDTCxRQUFRO0FBQUEsVUFDWjtBQUFBLFFBQ0o7QUFBQSxNQUNKO0FBQUEsSUFDSjtBQUFBLEVBQ0o7QUFDSixDQUFDOyIsCiAgIm5hbWVzIjogW10KfQo=

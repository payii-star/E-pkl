// Helper kecil biar import.meta.env cuma diakses dari file .ts (bukan langsung
// di dalam .vue) — di seluruh project ini, .vue yang akses import.meta.env
// langsung suka kena error TS "Property 'env' does not exist on type
// 'ImportMeta'" di Volar/VS Code.
//
// Dipakai (import.meta as any).env di sini secara sengaja: apapun kondisi
// env.d.ts / tsconfig di project ini, baris ini dijamin selalu compile,
// karena TypeScript nggak lagi coba mencocokkan ke tipe ImportMeta bawaan.
// Ini SATU-SATUNYA tempat di seluruh project yang boleh pakai trik ini —
// jangan disalin ke file lain, cukup import API_URL/BACKEND_URL dari sini.

const env = (import.meta as any).env;

export const API_URL: string = env.VITE_APP_API_URL;

// Origin backend tanpa suffix /api — dipakai buat akses file publik lewat
// /storage/... (mis. lampiran surat dokter, foto, dst)
export const BACKEND_URL: string = API_URL.replace(/\/api\/?$/, "");
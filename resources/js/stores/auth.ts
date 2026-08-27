import { ref } from "vue";
import { defineStore } from "pinia";
import ApiService from "@/core/services/ApiService";
import JwtService from "@/core/services/JwtService";

export interface User {
    id: number;
    uuid: string;
    name: string;
    email: string;
    phone: string;

    nim_nis?: string;
    asal_instansi?: string;
    asal_instansi_address?: string;
    asal_instansi_latitude?: number | null;
    asal_instansi_longitude?: number | null;
    asal_instansi_place_id?: string;

    photo?: string;
    password?: string;

    permission: string[];

    role?: {
        name: string;
        full_name: string;
    };
}

export const useAuthStore = defineStore("auth", () => {
    const error = ref<string | null>(null);

    const user = ref<User>({} as User);

    const isAuthenticated = ref(false);

    /**
     * Simpan data authentication
     */
    function setAuth(authUser: User, token = "") {
        if (!authUser) {
            console.error("setAuth dipanggil tanpa user.");
            return;
        }

        user.value = authUser;
        isAuthenticated.value = true;
        error.value = null;

        if (token) {
            JwtService.saveToken(token);
        }

        ApiService.setHeader();
    }

    /**
     * Hapus authentication
     */
    function purgeAuth() {
        isAuthenticated.value = false;
        user.value = {} as User;
        error.value = null;

        JwtService.destroyToken();
    }

    /**
     * LOGIN
     */
    async function login(credentials: any) {
        error.value = null;

        try {
            const response = await ApiService.post(
                "auth/login",
                credentials
            );

            const data = response.data;

            console.log("LOGIN RESPONSE:", data);

            if (!data?.token) {
                throw new Error("Token login tidak ditemukan.");
            }

            if (!data?.user) {
                throw new Error("Data user login tidak ditemukan.");
            }

            setAuth(data.user, data.token);

            return response;
        } catch (err: any) {
            console.error("LOGIN ERROR:", err);

            error.value =
                err?.response?.data?.message ||
                err?.message ||
                "Login gagal.";

            throw err;
        }
    }

    /**
     * LOGOUT
     */
    async function logout() {
        try {
            const token = JwtService.getToken();

            if (token) {
                ApiService.setHeader();

                try {
                    await ApiService.delete("auth/logout");
                } catch (err) {
                    console.warn(
                        "Request logout backend gagal, token tetap akan dihapus.",
                        err
                    );
                }
            }
        } finally {
            purgeAuth();
        }
    }

    /**
     * REGISTER
     */
    async function register(credentials: any) {
        error.value = null;

        try {
            const response = await ApiService.post(
                "auth/register",
                credentials
            );

            const data = response.data;

            console.log("REGISTER RESPONSE:", data);

            if (data?.token && data?.user) {
                setAuth(data.user, data.token);
            }

            return response;
        } catch (err: any) {
            console.error("REGISTER ERROR:", err);

            error.value =
                err?.response?.data?.message ||
                err?.message ||
                "Registrasi gagal.";

            throw err;
        }
    }

    /**
     * FORGOT PASSWORD
     */
    async function forgotPassword(email: string) {
        error.value = null;

        try {
            const response = await ApiService.post(
                "auth/forgot_password",
                {
                    email,
                }
            );

            return response;
        } catch (err: any) {
            console.error("FORGOT PASSWORD ERROR:", err);

            error.value =
                err?.response?.data?.message ||
                err?.message ||
                "Permintaan reset password gagal.";

            throw err;
        }
    }

    /**
     * VERIFY AUTH
     *
     * Dipanggil ketika aplikasi dibuka / refresh.
     */
    async function verifyAuth() {
        const token = JwtService.getToken();

        console.log("VERIFY AUTH TOKEN:", token ? "ADA" : "TIDAK ADA");

        if (!token) {
            isAuthenticated.value = false;
            return false;
        }

        try {
            ApiService.setHeader();

            const response = await ApiService.get("auth/me");

            const data = response.data;

            console.log("AUTH ME RESPONSE:", data);

            /**
             * Backend bisa mengembalikan:
             *
             * {
             *   user: {...}
             * }
             *
             * atau:
             *
             * {
             *   data: {
             *      user: {...}
             *   }
             * }
             *
             * atau langsung:
             *
             * {
             *   id: 1,
             *   name: "Admin HR",
             *   ...
             * }
             */

            const authUser =
                data?.user ||
                data?.data?.user ||
                data?.data ||
                data;

            if (!authUser || !authUser.id) {
                console.error(
                    "Response auth/me tidak memiliki data user yang valid."
                );

                /**
                 * Jangan langsung purge token di sini.
                 *
                 * Kita hanya menganggap authentication
                 * belum berhasil diverifikasi.
                 */
                isAuthenticated.value = false;

                return false;
            }

            setAuth(authUser);

            return true;
        } catch (err: any) {
            console.error("VERIFY AUTH ERROR:", err);

            /**
             * Hanya hapus token jika server benar-benar
             * menyatakan token tidak valid / unauthorized.
             */
            const status = err?.response?.status;

            if (status === 401 || status === 419) {
                purgeAuth();
            } else {
                /**
                 * Untuk error 500, network error, timeout,
                 * dll, jangan langsung menghapus token.
                 */
                console.warn(
                    "Auth verification gagal bukan karena unauthorized. Token dipertahankan."
                );

                isAuthenticated.value = false;
            }

            error.value =
                err?.response?.data?.message ||
                err?.message ||
                "Gagal memverifikasi autentikasi.";

            return false;
        }
    }

    return {
        error,
        user,
        isAuthenticated,

        login,
        logout,
        register,
        forgotPassword,
        verifyAuth,

        setAuth,
        purgeAuth,
    };
});
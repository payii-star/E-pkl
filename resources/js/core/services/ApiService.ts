    import type { App } from "vue";
    import type { AxiosResponse, AxiosRequestConfig } from "axios";
    import axios from "axios";
    import VueAxios from "vue-axios";
    import JwtService from "@/core/services/JwtService";

    /**
     * Service to call HTTP requests via Axios.
     */
    class ApiService {
    /**
     * Vue instance property.
     */
    public static vueInstance: App;

    /**
     * Initialize vue-axios.
     * @param app The Vue app instance.
     */
    public static init(app: App<Element>) {
        ApiService.vueInstance = app;
        ApiService.vueInstance.use(VueAxios, axios);
        ApiService.vueInstance.axios.defaults.baseURL = import.meta.env.VITE_APP_API_URL;
    }

    /**
     * Set the default HTTP request headers.
     */
    public static setHeader(): void {
        ApiService.vueInstance.axios.defaults.headers.common["Authorization"] = `Bearer ${JwtService.getToken()}`;
        ApiService.vueInstance.axios.defaults.headers.common["Accept"] = "application/json";
    }

    /**
     * Send a GET HTTP request with query parameters.
     * @param resource The resource endpoint.
     * @param params The request parameters.
     * @returns Promise<AxiosResponse>
     */
    public static query(resource: string, params = {} as object): Promise<AxiosResponse> {
        this.setHeader();
        return ApiService.vueInstance.axios.get(resource, { params });
    }

    /**
     * Send a GET HTTP request.
     * @param resource The resource endpoint.
     * @param slug An optional slug to append to the resource.
     * @returns Promise<AxiosResponse>
     */
    public static get(resource: string, slug = "" as string): Promise<AxiosResponse> {
        this.setHeader();
        // Logika baru: hanya tambahkan slash jika slug ada isinya
        const fullUrl = slug ? `${resource}/${slug}` : resource;
        return ApiService.vueInstance.axios.get(fullUrl);
    }

    /**
     * Send a POST HTTP request.
     * @param resource The resource endpoint.
     * @param params The request payload.
     * @returns Promise<AxiosResponse>
     */
    public static post(resource: string, params: any): Promise<AxiosResponse> {
        return ApiService.vueInstance.axios.post(`${resource}`, params);
    }

    /**
     * Send an UPDATE HTTP request (PUT).
     * @param resource The resource endpoint.
     * @param slug The slug of the resource to update.
     * @param params The request payload.
     * @returns Promise<AxiosResponse>
     */
    public static update(resource: string, slug: string, params: any): Promise<AxiosResponse> {
        return ApiService.vueInstance.axios.put(`${resource}/${slug}`, params);
    }

    /**
     * Send a PUT HTTP request.
     * @param resource The resource endpoint.
     * @param params The request payload.
     * @returns Promise<AxiosResponse>
     */
    public static put(resource: string, params: any): Promise<AxiosResponse> {
        return ApiService.vueInstance.axios.put(`${resource}`, params);
    }

    /**
     * Send a DELETE HTTP request.
     * @param resource The resource endpoint.
     * @returns Promise<AxiosResponse>
     */
    public static delete(resource: string): Promise<AxiosResponse> {
        return ApiService.vueInstance.axios.delete(resource);
    }
    }

    export default ApiService;
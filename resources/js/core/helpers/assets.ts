import { illustrationsSet } from "@/layouts/default-layout/config/helper";
import { useThemeStore } from "@/stores/theme";

const normalizeBaseUrl = (): string => {
    const base = String(import.meta.env.VITE_BASE_URL ?? "").trim();

    if (!base || base === "undefined" || base === "null") {
        return "";
    }

    return base.replace(/\/+$/, "");
};

const buildUrl = (path: string): string => {
    const cleanPath = path.replace(/^\/+/, "");
    const base = normalizeBaseUrl();

    if (!base) {
        return `/${cleanPath}`;
    }

    return `${base}/${cleanPath}`;
};

export const getIllustrationsPath = (illustrationName: string): string => {
    const extension = illustrationName.substring(
        illustrationName.lastIndexOf("."),
        illustrationName.length
    );
    const illustration =
        useThemeStore().mode == "dark"
            ? `${illustrationName.substring(
                  0,
                  illustrationName.lastIndexOf(".")
              )}-dark`
            : illustrationName.substring(0, illustrationName.lastIndexOf("."));
    return buildUrl(
        `media/illustrations/${illustrationsSet.value}/${illustration}${extension}`
    );
};

export const getAssetPath = (path: string): string => {
    return buildUrl(path);
};

export const getStoragePath = (path: string): string => {
    return buildUrl(`storage/${path}`);
};

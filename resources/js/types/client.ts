export interface ClientLogo {
    id?: number | string;
    url?: string;
    name: string;
    short?: string;
    logo?: File | string | Array<File | string>;
    order?: number;
    is_active?: boolean;
}

export interface ClientLogoPayload {
    id?: number | string;
    name?: string;
    short?: string;
    url?: string;
    logo?: File | string | Array<File | string>;
    order?: number;
    is_active?: boolean;
}

export interface ClientLogoListResponse {
    data: ClientLogo[];
    message?: string;
}

export interface FooterSetting {
    company_name: string;
    description: string;
    address?: string;
    email?: string;
    phone?: string;
    copyright?: string;
}

export interface FooterSocial {
    id?: number;
    uuid?: string;
    platform: string;
    url: string;
}
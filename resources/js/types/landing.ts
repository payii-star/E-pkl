export interface ClientLogo {
    id: BigInteger;
    name: string;
    short?: string;
    logo?: Array<File | string> | string;
    urutan: number;
    is_active: boolean;
}

export interface Menu {
    id: BigInteger;
    name: string;
    url: string;
    order: number;
    is_active: boolean;
}

export interface Service {
    id: BigInteger;
    title: string;
    description?: string;
    icon?: string;
    order: number;
    is_active: boolean;
}

export interface Statistic {
    id: BigInteger;
    icon: string;
    statistic: string;
    label: string;
    urutan: number;
    is_active: boolean;
}

export interface Team {
    id: BigInteger;
    name: string;
    position: string;
    image?: Array<File | string> | string;
    order: number;
    is_active: boolean;
}

export interface Testimonial {
    id: BigInteger;
    name: string;
    position?: string;
    photo?: Array<File | string> | string;
    message: string;
    placement: "beranda" | "services";
    order: number;
    is_active: boolean;
}

export interface ContactMessage {
    id: BigInteger;
    name: string;
    email: string;
    phone?: string;
    subject?: string;
    message: string;
    is_read: boolean;
    created_at: string;
}

export interface FooterSetting {
    id?: BigInteger;
    company_name: string;
    description: string;
    address?: string;
    email?: string;
    phone?: string;
    copyright?: string;
}

export interface FooterSocial {
    id: BigInteger;
    platform: string;
    url: string;
}

export interface Project {
    id: BigInteger;
    title: string;
    slug?: string;
    description?: string;
    thumbnail?: Array<File | string> | string;
    category?: string;
    url?: string;
    is_featured: boolean;
    urutan: number;
}

export interface Project {
    id?: BigInteger;
    uuid?: string;
    slug?: string;
    title: string;
    client_name?: string;
    description?: string;
    image?: string;
    link_project?: string;
    thumbnail?: string;
    gallery?: string[];
    url?: string;
    is_featured: boolean;
    urutan: number;
}
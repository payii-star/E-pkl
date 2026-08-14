export interface Project {
    id?: BigInteger;
    uuid?: string;
    slug?: string;
    title: string;
    description?: string;
    image?: string;
    link_project?: string;
    thumbnail?: string;
    url?: string;
    is_featured: boolean;
    urutan: number;
}
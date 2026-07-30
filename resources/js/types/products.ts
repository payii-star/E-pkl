    // resources/js/types/products.ts

    // Tipe data untuk Kategori
    export interface Category {
        id: number;
        name: string;
    }

    // Tipe untuk "Jenis Varian" (misal: Warna, Ukuran)
    export interface Variant {
        id: number;
        name: string;
        options: VariantOption[];
    }
    
    // Tipe untuk "Pilihan Varian" (misal: Merah, S, L)
    export interface VariantOption {
        id: number;
        name: string;
    }
    
    // Tipe data untuk Varian Produk (SKU, harga, stok, dll.)
    // Ini yang akan kita gunakan di mana-mana  
    export interface ProductVariant {
        id: number;
        sku: string;
        price: number;
        stock: number;
        reserved_stock: number; 
        options: { [key: string]: string };
        product: { // Info produk induk yang menempel
        id: number;
        name: string;
        };

        available_stock?: number;
    }
    
    // Tipe data untuk Produk Induk (hanya berisi info umum dan daftar variannya)
    export interface Product {
        id: number;
        name: string;
        category: Category | null;
        category_id: number | null;
        variants: ProductVariant[];
        image_path: string | null; 
        image_url: string;    
    }
    
    // Tipe data untuk item di dalam Keranjang Belanja
    // Ini adalah ProductVariant ditambah properti 'quantity'
    export interface CartItem extends ProductVariant {
        quantity: number;
    }

    export interface GeneratedVariant {
        id?: number | null; // ID bisa ada (jika varian lama) atau tidak
        name: string;      // ex: "Merah / S"
        sku: string;
        price: number;
        stock: number;
        options: { [key:string]: string };
    }
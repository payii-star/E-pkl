<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    /**
     * Menampilkan daftar produk.
     */
public function index(Request $request)
{
    $query = Product::with(['category', 'variants']);

    if ($request->filled('search')) {
        $searchTerm = trim($request->search);
        $query->where(function ($q) use ($searchTerm) {
            $q->where('name', 'like', "%{$searchTerm}%")
                ->orWhereHas('variants', function ($variantQuery) use ($searchTerm) {
                    $variantQuery->where('sku', 'like', "%{$searchTerm}%");
                });
        });
    }

    $products = $request->get('all') === 'true'
        ? $query->latest()->get()
        : $query->latest()->paginate(10);
    
    // Definisikan logika untuk menambahkan available_stock
    $transformer = function ($product) {
        if ($product->variants) { // Pastikan varian ada sebelum di-loop
            $product->variants->each(function ($variant) {
                $variant->append('available_stock');
            });
        }
        return $product;
    };

    // Cek apakah hasilnya adalah Paginator atau Collection biasa
    if ($products instanceof \Illuminate\Pagination\AbstractPaginator) {
        // Jika Paginator, transform isinya
        $products->getCollection()->transform($transformer);
    } else {
        // Jika Collection biasa, langsung transform
        $products->transform($transformer);
    }
    
    return response()->json($products);
}

    /**
     * Menyimpan produk baru.
     * Menggunakan StoreProductRequest untuk validasi.
     */
    public function store(StoreProductRequest $request)
    {
        $validatedData = $request->validated();

        DB::beginTransaction();
        try {
            $productData = [
                'name' => $validatedData['name'],
                'category_id' => $validatedData['category_id'],
            ];

            if ($request->hasFile('image')) {
                $path = $request->file('image')->store('products', 'public');
                $productData['image_path'] = $path; // Simpan path saja
            }
    
            $product = Product::create($productData);
    
            if (empty($validatedData['variants'])) {
                // Produk Simpel
                $product->variants()->create([
                    'sku' => $validatedData['sku'],
                    'price' => $validatedData['price'],
                    'stock' => $validatedData['stock'],
                    'options' => ['default' => 'default'],
                ]);
            } else {
                // Produk dengan Varian
                $product->variants()->createMany($validatedData['variants']);
            }
            
            DB::commit();
            return response()->json($product->load('variants'), 201);
    
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Product creation failed: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to create product', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     * Kita perlu method ini untuk mengambil data produk saat akan diedit.
     */
    public function show(Product $product)
    {
        // Load relasi yang mungkin dibutuhkan di form edit
        $product->load(['category', 'variants']); 
        return response()->json($product);
    }

/**
     * Memperbarui produk yang ada.
     */
    public function update(UpdateProductRequest $request, Product $product)
    {
        $validatedData = $request->validated();
    
        DB::beginTransaction();
        try {
            // 1. Update Data Produk Utama
            $productData = [
                'name' => $validatedData['name'],
                'category_id' => $validatedData['category_id'],
            ];

            if ($request->hasFile('image')) {
                if ($product->image_path && Storage::disk('public')->exists($product->image_path)) {
                    Storage::disk('public')->delete($product->image_path);
                }
                $path = $request->file('image')->store('products', 'public');
                $productData['image_path'] = $path;
            }

            $product->update($productData);
    
            // 2. Cek Tipe Update: Simpel atau Varian?
            
            // KASUS A: UPDATE PRODUK SIMPEL
            // (Jika data 'variants' kosong/tidak dikirim, berarti ini produk simpel)
            if (empty($validatedData['variants'])) {
                
                // Ambil satu-satunya varian yang dimiliki produk ini
                $variant = $product->variants()->first();
                
                if ($variant) {
                    // Update data varian tunggal tersebut
                    $variant->update([
                        'sku' => $validatedData['sku'],
                        'price' => $validatedData['price'],
                        'stock' => $validatedData['stock'],
                        // Pastikan options tetap default atau sesuai kebutuhan
                    ]);
                } else {
                    // Jaga-jaga jika varian belum ada (seharusnya tidak terjadi)
                    $product->variants()->create([
                        'sku' => $validatedData['sku'],
                        'price' => $validatedData['price'],
                        'stock' => $validatedData['stock'],
                        'options' => ['default' => 'default'],
                    ]);
                }

            } else {
                // KASUS B: UPDATE PRODUK DENGAN VARIAN BANYAK
                
                // Ambil ID varian yang dikirim dari frontend (untuk yang dipertahankan)
                $incomingVariantIds = collect($validatedData['variants'])
                    ->pluck('id')
                    ->filter(); // Hapus yang null (varian baru)
                
                // Hapus varian lama yang ID-nya TIDAK ada di data baru
                $product->variants()->whereNotIn('id', $incomingVariantIds)->delete();
        
                // Loop untuk update atau create varian
                foreach ($validatedData['variants'] as $variantData) {
                    $product->variants()->updateOrCreate(
                        ['id' => $variantData['id'] ?? null], // Cari berdasarkan ID (jika ada)
                        $variantData // Data untuk diupdate/create
                    );
                }
            }
            
            DB::commit();
            return response()->json($product->load('variants'));
    
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Product update failed: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to update product', 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Menghapus produk.
     */
    public function destroy(Product $product)
    {
        if ($product->image_path && Storage::disk('public')->exists($product->image_path)) {
            Storage::disk('public')->delete($product->image_path);
        }
        $product->delete();
        return response()->json(null, 204);
    }


/**
 * Mengambil varian untuk kasir.
 */
public function getVariantsForCashier()
{
    $variants = ProductVariant::with('product:id,name,image_path')->get();

    $availableVariants = $variants
        ->append('available_stock') // Tambahkan nilai available_stock
        ->filter(function ($variant) {
            return $variant->available_stock > 0; // Filter hanya yang bisa dijual
        });

    return response()->json($availableVariants->values());
}

// findByBarcode juga tetap perlu di-append agar kasir melihat info yang benar
public function findByBarcode($barcode)
    {
        $variant = ProductVariant::with('product')
            ->where(function($query) use ($barcode) {
                // Cari di kolom 'barcode' ATAU 'sku'
                $query->where('barcode', $barcode)
                        ->orWhere('sku', $barcode);
            })
            ->first();

        if (!$variant) {
            return response()->json(['message' => 'Product not found'], 404);
        }
        
        $variant->append('available_stock');
        return response()->json($variant);
    }
/**
 * Mengambil semua varian untuk halaman overview stok admin.
 */
public function stockOverview(Request $request)
{
    // Kita tidak memfilter berdasarkan stok di sini
    $variants = ProductVariant::with('product:id,name')
        ->latest('id')
        ->paginate(20);

    // Lampirkan accessor ke setiap item dalam koleksi paginasi
    $variants->getCollection()->transform(function ($variant) {
        return $variant->append('available_stock');
    });

    return response()->json($variants);
}
}
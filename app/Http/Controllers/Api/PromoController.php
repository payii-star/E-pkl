<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Promo;
use Illuminate\Http\Request;

class PromoController extends Controller
{
    public function validatePromo(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'member_id' => 'nullable|integer|exists:members,id'
        ]);
    
        $promo = Promo::where('code', $request->code)->first();
    
        if (!$promo) {
            return response()->json(['message' => 'Kode promo tidak ditemukan.'], 404);
        }
    
        // Pengecekan baru: Jika promo khusus member, pastikan member_id ada
        if ($promo->is_member_only && !$request->member_id) {
            return response()->json(['message' => 'Promo ini hanya berlaku untuk member.'], 422); // 422 Unprocessable Entity
        }
        
        // Validasi lain (aktif, tanggal, dll.)
        $isActive = $promo->is_active &&
                    ($promo->start_date == null || $promo->start_date <= now()) &&
                    ($promo->end_date == null || $promo->end_date >= now());
    
        if (!$isActive) {
            return response()->json(['message' => 'Kode promo tidak valid atau sudah kedaluwarsa.'], 404);
        }
    
        return response()->json($promo);
    }

    public function index()
    {
        return Promo::latest()->paginate(10);
    }

    public function store(Request $request)
    {

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:promos,code',
            'type' => 'required|in:percentage,fixed_amount',
            'value' => 'required|numeric|min:0',
            'min_purchase' => 'nullable|numeric|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'is_active' => 'boolean',
            'is_member_only' => 'boolean',
        ]);

        $promo = Promo::create($validated);
        return response()->json($promo, 201);
    }

    public function show(Promo $promo)
    {
        return $promo;
    }

    public function update(Request $request, Promo $promo)
    {

        // Pastikan semua field divalidasi, sama seperti di 'store'
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:promos,code,' . $promo->id,
            'type' => 'required|in:percentage,fixed_amount',
            'value' => 'required|numeric|min:0',
            'min_purchase' => 'nullable|numeric|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'is_active' => 'required|boolean', 
            'is_member_only' => 'required|boolean', 
        ]);

        $promo->update($validated);
        return response()->json($promo);
    }

    public function destroy(Promo $promo)
    {
        $promo->delete();
        return response()->json(null, 204);
    }
}
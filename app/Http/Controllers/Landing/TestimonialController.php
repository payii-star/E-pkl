<?php

namespace App\Http\Controllers\Landing;

use App\Http\Controllers\Controller;
use App\Models\LandingTestimonial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TestimonialController extends Controller
{
    public function index()
    {
        return response()->json(['data' => LandingTestimonial::orderBy('order')->get()]);
    }

    public function show(string $id)
    {
        return response()->json(['data' => LandingTestimonial::findOrFail($id)]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'position' => 'nullable|string|max:255',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'message' => 'required|string',
            'placement' => 'required|in:beranda,services',
            'order' => 'nullable|integer',
        ]);

        if ($request->hasFile('photo')) {
            $validated['photo'] = '/storage/' . $request->file('photo')->store('landing/testimonials', 'public');
        }

        $data = LandingTestimonial::create($validated);
        return response()->json(['message' => 'Testimoni berhasil ditambahkan', 'data' => $data], 201);
    }

    public function update(Request $request, string $id)
    {
        $testimonial = LandingTestimonial::findOrFail($id);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'position' => 'nullable|string|max:255',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'message' => 'required|string',
            'placement' => 'required|in:beranda,services',
            'order' => 'nullable|integer',
        ]);

        if ($request->hasFile('photo')) {
            if ($testimonial->photo) {
                Storage::disk('public')->delete(str_replace('/storage/', '', $testimonial->photo));
            }
            $validated['photo'] = '/storage/' . $request->file('photo')->store('landing/testimonials', 'public');
        }

        $testimonial->update($validated);
        return response()->json(['message' => 'Testimoni berhasil diperbarui', 'data' => $testimonial]);
    }

    public function destroy(string $id)
    {
        $testimonial = LandingTestimonial::findOrFail($id);
        if ($testimonial->photo) {
            Storage::disk('public')->delete(str_replace('/storage/', '', $testimonial->photo));
        }
        $testimonial->delete();
        return response()->json(['message' => 'Testimoni berhasil dihapus']);
    }
}

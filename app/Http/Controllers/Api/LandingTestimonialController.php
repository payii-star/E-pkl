<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LandingTestimonial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class LandingTestimonialController extends Controller
{
    public function adminIndex()
    {
        return response()->json([
            'success' => true, 
            'data' => LandingTestimonial::orderBy('urutan')->orderBy('id')->get()
        ]);
    }

    public function publicIndex()
    {
        return response()->json([
            'success' => true, 
            'data' => LandingTestimonial::where('is_active', true)->orderBy('urutan')->get()
        ]);
    }

    public function show(LandingTestimonial $testimonial)
    {
        return response()->json([
            'success' => true, 
            'data' => $testimonial
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'      => 'required|string|max:255',
            'role'      => 'nullable|string|max:255',
            'company'   => 'nullable|string|max:255',
            'message'   => 'required|string',
            'rating'    => 'nullable|integer|min:1|max:5',
            'urutan'    => 'nullable|integer',
            'is_active' => 'nullable|boolean',
            'avatar'    => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(), 
                'errors'  => $validator->errors()
            ], 422);
        }

        $data = $validator->safe()->except('avatar');
        $data['urutan'] = $data['urutan'] ?? ((int) (LandingTestimonial::max('urutan') ?? 0) + 1);
        $data['is_active'] = $request->boolean('is_active', true);

        if ($request->hasFile('avatar')) {
            $data['avatar'] = $request->file('avatar')->store('landing/testimonials', 'public');
        }

        $testimonial = LandingTestimonial::create($data);

        return response()->json([
            'success' => true, 
            'message' => 'Testimonial berhasil ditambahkan', 
            'data'    => $testimonial
        ], 201);
    }

    public function update(Request $request, LandingTestimonial $testimonial)
    {
        $validator = Validator::make($request->all(), [
            'name'      => 'required|string|max:255',
            'role'      => 'nullable|string|max:255',
            'company'   => 'nullable|string|max:255',
            'message'   => 'required|string',
            'rating'    => 'nullable|integer|min:1|max:5',
            'urutan'    => 'nullable|integer',
            'is_active' => 'nullable|boolean',
            'avatar'    => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(), 
                'errors'  => $validator->errors()
            ], 422);
        }

        $data = $validator->safe()->except('avatar');
        $data['is_active'] = $request->boolean('is_active', true);

        if ($request->hasFile('avatar')) {
            if ($testimonial->avatar) {
                Storage::disk('public')->delete($testimonial->avatar);
            }
            $data['avatar'] = $request->file('avatar')->store('landing/testimonials', 'public');
        }

        $testimonial->update($data);

        return response()->json([
            'success' => true, 
            'message' => 'Testimonial berhasil diperbarui', 
            'data'    => $testimonial
        ]);
    }

    public function destroy(LandingTestimonial $testimonial)
    {
        if ($testimonial->avatar) {
            Storage::disk('public')->delete($testimonial->avatar);
        }

        $testimonial->delete();

        return response()->json([
            'success' => true, 
            'message' => 'Testimonial berhasil dihapus'
        ]);
    }
}
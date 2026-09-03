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
        $testimonials = LandingTestimonial::orderBy('order')->orderBy('id')->get();

        return response()->json([
            'success' => true,
            'data' => $testimonials->map(
                fn (LandingTestimonial $t) => $this->format($t)
            ),
        ]);
    }

    public function publicIndex(Request $request)
    {
        $query = LandingTestimonial::where('is_active', true)->orderBy('order');

        // Landing bisa filter per section: ?placement=beranda atau ?placement=services
        if ($request->filled('placement')) {
            $query->where('placement', $request->placement);
        }

        return response()->json([
            'success' => true,
            'data' => $query->get()
        ]);
    }

    public function show(LandingTestimonial $testimonial)
    {
        return response()->json([
            'success' => true,
            'data' => $this->format($testimonial),
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'      => 'required|string|max:255',
            'position'  => 'nullable|string|max:255',
            'message'   => 'required|string',
            'placement' => 'required|in:beranda,services',
            'order'     => 'nullable|integer',
            'is_active' => 'nullable|boolean',
            'photo'     => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
                'errors'  => $validator->errors()
            ], 422);
        }

        $data = $validator->safe()->except('photo');
        $data['order'] = $data['order'] ?? ((int) (LandingTestimonial::max('order') ?? 0) + 1);
        $data['is_active'] = $request->boolean('is_active', true);

        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('landing/testimonials', 'public');
        }

        $testimonial = LandingTestimonial::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Testimonial berhasil ditambahkan',
            'data'    => $this->format($testimonial),
        ], 201);
    }

    public function update(Request $request, LandingTestimonial $testimonial)
    {
        $validator = Validator::make($request->all(), [
            'name'      => 'required|string|max:255',
            'position'  => 'nullable|string|max:255',
            'message'   => 'required|string',
            'placement' => 'required|in:beranda,services',
            'order'     => 'nullable|integer',
            'is_active' => 'nullable|boolean',
            'photo'     => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
                'errors'  => $validator->errors()
            ], 422);
        }

        $data = $validator->safe()->except('photo');
        $data['is_active'] = $request->boolean('is_active', $testimonial->is_active);

        if ($request->hasFile('photo')) {
            if ($testimonial->photo) {
                Storage::disk('public')->delete($testimonial->photo);
            }
            $data['photo'] = $request->file('photo')->store('landing/testimonials', 'public');
        }

        $testimonial->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Testimonial berhasil diperbarui',
            'data'    => $this->format($testimonial),
        ]);
    }

    public function destroy(LandingTestimonial $testimonial)
    {
        if ($testimonial->photo) {
            Storage::disk('public')->delete($testimonial->photo);
        }

        $testimonial->delete();

        return response()->json([
            'success' => true,
            'message' => 'Testimonial berhasil dihapus'
        ]);
    }

    private function format(LandingTestimonial $testimonial): array
    {
        return [
            'id'        => $testimonial->id,
            'uuid'      => (string) $testimonial->id,
            'name'      => $testimonial->name,
            'position'  => $testimonial->position,
            'photo'     => $testimonial->photo,
            'message'   => $testimonial->message,
            'placement' => $testimonial->placement,
            'order'     => $testimonial->order,
            'is_active' => $testimonial->is_active,
        ];
    }
}
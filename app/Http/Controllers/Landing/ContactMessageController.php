<?php

namespace App\Http\Controllers\Landing;

use App\Http\Controllers\Controller;
use App\Models\LandingContactMessage;
use Illuminate\Http\Request;

class ContactMessageController extends Controller
{
    // Dipanggil dari form kontak publik di Landing (tanpa auth)
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:50',
            'subject' => 'nullable|string|max:255',
            'message' => 'required|string',
        ]);

        $data = LandingContactMessage::create($validated);
        return response()->json(['message' => 'Pesan berhasil dikirim', 'data' => $data], 201);
    }

    // Untuk admin (dashboard)
    public function index(Request $request)
    {
        $per = $request->per ?? 10;
        $data = LandingContactMessage::latest()->paginate($per);
        return response()->json($data);
    }

    public function show(string $id)
    {
        $message = LandingContactMessage::findOrFail($id);
        $message->update(['is_read' => true]);
        return response()->json(['contact_message' => $message]);
    }

    public function destroy(string $id)
    {
        LandingContactMessage::findOrFail($id)->delete();
        return response()->json(['message' => 'Pesan berhasil dihapus']);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Journal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class JournalController extends Controller
{
    // POST /journals
    public function store(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
            'kegiatan' => 'required|string',
            'kendala' => 'nullable|string',
            'solusi' => 'nullable|string',
            'foto' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:1024',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $fotoPath = null;
        if ($request->hasFile('foto')) {
            $fotoPath = $request->file('foto')->store('journals', 'public');
        }

        $journal = Journal::create([
            'user_id' => $user->id,
            'date' => $request->date,
            'kegiatan' => $request->kegiatan,
            'kendala' => $request->kendala,
            'solusi' => $request->solusi,
            'foto' => $fotoPath,
            'status' => 'pending',
        ]);

        return response()->json(['data' => $journal], 201);
    }

    // GET /journals/history
    public function history(Request $request)
    {
        $user = $request->user();

        $journals = Journal::where('user_id', $user->id)
            ->latest('date')
            ->get();

        return response()->json(['data' => $journals]);
    }

    // GET /journals/pending-approval -> untuk pembimbing/admin
    public function pendingApproval(Request $request)
    {
        $userId = $request->user()->id;

        $journals = Journal::whereHas('user', function ($q) use ($userId) {
            $q->where('atasan_id', $userId);
            })
            ->where('status', 'pending')
            ->with('user')
            ->latest('date')
            ->get();

        return response()->json(['data' => $journals]);
    }

    // POST /journals/{journal}/approve
    public function approve(Request $request, Journal $journal)
    {
        $this->authorizeApprover($request, $journal);

        $journal->update([
            'status' => 'approved',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        return response()->json(['data' => $journal]);
    }

    // POST /journals/{journal}/reject
    public function reject(Request $request, Journal $journal)
    {
        $this->authorizeApprover($request, $journal);

        $validator = Validator::make($request->all(), [
            'catatan_approval' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $journal->update([
            'status' => 'rejected',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
            'catatan_approval' => $request->catatan_approval,
        ]);

        return response()->json(['data' => $journal]);
    }

    private function authorizeApprover(Request $request, Journal $journal)
    {
        $journal->loadMissing('user');
        if ($journal->user?->atasan_id !== $request->user()->id) {
            abort(403, 'Kamu bukan pembimbing dari peserta magang ini');
        }
    }
}
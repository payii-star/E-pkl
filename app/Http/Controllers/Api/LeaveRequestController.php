<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class LeaveRequestController extends Controller
{
    // GET /leave-requests -> daftar izin milik user yang sedang login
    public function index(Request $request)
    {
        $leaveRequests = LeaveRequest::where('user_id', $request->user()->id)
            ->latest('date')
            ->get();

        return response()->json(['data' => $leaveRequests]);
    }

    // POST /leave-requests -> user mengajukan izin tidak masuk
    public function store(Request $request)
    {
        $userId = $request->user()->id;

        $validator = Validator::make($request->all(), [
            'date' => [
                'required',
                'date',
                // Cegah spam: 1 user cuma boleh punya 1 pengajuan izin untuk tanggal yang sama
                Rule::unique('leave_requests', 'date')
                    ->where(fn ($query) => $query->where('user_id', $userId)),
            ],
            'reason_type' => 'required|in:tanpa_keterangan,sakit,acara_keluarga',
            'note' => 'nullable|string|max:1000',
            // surat dokter wajib dilampirkan khusus alasan "sakit"
            'attachment' => [
                'nullable',
                'file',
                'mimes:jpg,jpeg,png,pdf',
                'max:2048',
                $request->reason_type === 'sakit' ? 'required' : 'nullable',
            ],
        ], [
            'date.unique' => 'Kamu sudah pernah mengajukan izin untuk tanggal ini',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = [
            'user_id' => $userId,
            'date' => $request->date,
            'reason_type' => $request->reason_type,
            'note' => $request->note,
            'status' => 'pending',
        ];

        if ($request->hasFile('attachment')) {
            $data['attachment'] = $request->file('attachment')->store('leave-requests', 'public');
        }

        $leaveRequest = LeaveRequest::create($data);

        return response()->json([
            'data' => $leaveRequest,
            'message' => 'Pengajuan izin berhasil dikirim',
        ], 201);
    }

    // POST /leave-requests/{leaveRequest} (dengan _method=PUT) -> user edit izin miliknya sendiri
    // Hanya bisa selama status masih "pending" (belum direview admin).
    public function update(Request $request, LeaveRequest $leaveRequest)
    {
        $userId = $request->user()->id;

        if ($leaveRequest->user_id !== $userId) {
            return response()->json(['message' => 'Tidak diizinkan'], 403);
        }

        if ($leaveRequest->status !== 'pending') {
            return response()->json([
                'message' => 'Izin yang sudah direview tidak bisa diedit lagi',
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'date' => [
                'required',
                'date',
                // Cegah spam, tapi abaikan record ini sendiri saat cek duplikat
                Rule::unique('leave_requests', 'date')
                    ->where(fn ($query) => $query->where('user_id', $userId))
                    ->ignore($leaveRequest->id),
            ],
            'reason_type' => 'required|in:tanpa_keterangan,sakit,acara_keluarga',
            'note' => 'nullable|string|max:1000',
            'attachment' => [
                'nullable',
                'file',
                'mimes:jpg,jpeg,png,pdf',
                'max:2048',
                // Wajib upload ulang HANYA kalau alasan "sakit" dan belum ada lampiran sama sekali
                ($request->reason_type === 'sakit' && !$leaveRequest->attachment) ? 'required' : 'nullable',
            ],
        ], [
            'date.unique' => 'Kamu sudah pernah mengajukan izin untuk tanggal ini',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = [
            'date' => $request->date,
            'reason_type' => $request->reason_type,
            'note' => $request->note,
        ];

        if ($request->hasFile('attachment')) {
            // Hapus lampiran lama kalau ada, baru simpan yang baru
            if ($leaveRequest->attachment) {
                Storage::disk('public')->delete($leaveRequest->attachment);
            }
            $data['attachment'] = $request->file('attachment')->store('leave-requests', 'public');
        }

        $leaveRequest->update($data);

        return response()->json([
            'data' => $leaveRequest->fresh(),
            'message' => 'Pengajuan izin berhasil diperbarui',
        ]);
    }

    // GET /admin/leave-requests -> semua pengajuan izin (buat hr-admin)
    public function adminIndex(Request $request)
    {
        $leaveRequests = LeaveRequest::with('user:id,name,email')
            ->when($request->status, fn($q, $status) => $q->where('status', $status))
            ->latest('date')
            ->get();

        return response()->json(['data' => $leaveRequests]);
    }

    // PATCH /admin/leave-requests/{leaveRequest}/status -> hr-admin approve/reject
    public function updateStatus(Request $request, LeaveRequest $leaveRequest)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:approved,rejected',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $leaveRequest->update([
            'status' => $request->status,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        if ($request->status === 'approved') {
            $today = now()->setTimezone(config('app.timezone', 'Asia/Jakarta'))->toDateString();
            $dateColumn = Attendance::dateColumn();
            $checkInTimeColumn = Attendance::checkInTimeColumn();
            $checkOutTimeColumn = Attendance::checkOutTimeColumn();

            $attendance = Attendance::where('user_id', $leaveRequest->user_id)
                ->where($dateColumn, $today)
                ->first();

            if ($attendance && $attendance->{$checkInTimeColumn} && !$attendance->{$checkOutTimeColumn}) {
                $attendance->update([
                    $checkOutTimeColumn => now()
                        ->setTimezone(config('app.timezone', 'Asia/Jakarta'))
                        ->toTimeString(),
                ]);
            }
        }

        return response()->json([
            'data' => $leaveRequest->load('user:id,name,email', 'reviewer:id,name'),
            'message' => $request->status === 'approved' ? 'Izin disetujui' : 'Izin ditolak',
        ]);
    }
}
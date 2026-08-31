<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

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
        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
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
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = [
            'user_id' => $request->user()->id,
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

        return response()->json([
            'data' => $leaveRequest->load('user:id,name,email', 'reviewer:id,name'),
            'message' => $request->status === 'approved' ? 'Izin disetujui' : 'Izin ditolak',
        ]);
    }
}
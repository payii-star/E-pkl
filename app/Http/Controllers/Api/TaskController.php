<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class TaskController extends Controller
{
    // GET /intern/tasks -> tugas milik user yang sedang login
    public function index(Request $request)
    {
        $user = $request->user();

        $tasks = Task::where('user_id', $user->id)
            ->with('creator:id,name')
            ->orderByRaw("FIELD(status, 'revisi', 'sedang', 'belum', 'submitted', 'selesai', 'ditolak')")
            ->orderBy('due_date')
            ->get();

        return response()->json(['data' => $tasks]);
    }

    // GET /admin/tasks -> semua tugas yang pernah diberikan (buat hr-admin)
    public function adminIndex(Request $request)
    {
        $tasks = Task::with(['user:id,name,email', 'creator:id,name'])
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['data' => $tasks]);
    }

    // POST /admin/tasks -> admin/pembimbing memberi tugas ke user tertentu
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $task = Task::create([
            'user_id' => $request->user_id,
            'created_by' => $request->user()->id,
            'title' => $request->title,
            'description' => $request->description,
            'due_date' => $request->due_date,
            'status' => 'belum',
        ]);

        return response()->json(['data' => $task->load('user:id,name,email')], 201);
    }

    // DELETE /admin/tasks/{task} -> hr-admin batalkan/hapus tugas yang sudah diberikan
    public function destroy(Task $task)
    {
        if ($task->attachment) {
            Storage::disk('public')->delete($task->attachment);
        }

        $task->delete();

        return response()->json(['message' => 'Tugas berhasil dihapus']);
    }

    // PATCH /tasks/{task}/status -> user update progress (belum <-> sedang) tugasnya sendiri
    public function updateStatus(Request $request, Task $task)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:belum,sedang',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $user = $request->user();
        if ($task->user_id !== $user->id) {
            return response()->json(['message' => 'Tidak diizinkan'], 403);
        }

        $task->update(['status' => $request->status]);

        return response()->json(['data' => $task]);
    }

    // POST /tasks/{task}/submit -> intern mengumpulkan tugas (file/gambar + catatan)
    public function submit(Request $request, Task $task)
    {
        $user = $request->user();
        if ($task->user_id !== $user->id) {
            return response()->json(['message' => 'Tidak diizinkan'], 403);
        }

        if (in_array($task->status, ['selesai'])) {
            return response()->json(['message' => 'Tugas ini sudah selesai, tidak bisa dikumpulkan lagi'], 422);
        }

        $validator = Validator::make($request->all(), [
            'submission_note' => 'nullable|string',
            'attachment' => 'required|file|mimes:jpeg,png,jpg,webp,pdf,doc,docx,xls,xlsx,zip|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        // Hapus file lama kalau ada (resubmit setelah revisi/ditolak)
        if ($task->attachment) {
            Storage::disk('public')->delete($task->attachment);
        }

        $task->update([
            'attachment' => $request->file('attachment')->store('tasks/submissions', 'public'),
            'submission_note' => $request->submission_note,
            'status' => 'submitted',
            'submitted_at' => now(),
            // Reset catatan review lama begitu ada pengumpulan baru
            'admin_note' => null,
            'reviewed_at' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tugas berhasil dikumpulkan',
            'data' => $task,
        ]);
    }

    // POST /admin/tasks/{task}/review -> admin menerima, menolak, atau minta revisi
    public function review(Request $request, Task $task)
    {
        $validator = Validator::make($request->all(), [
            'action' => 'required|in:accept,reject,revise',
            'admin_note' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        if ($task->status !== 'submitted') {
            return response()->json(['message' => 'Tugas ini belum dikumpulkan, belum bisa direview'], 422);
        }

        $statusMap = [
            'accept' => 'selesai',
            'reject' => 'ditolak',
            'revise' => 'revisi',
        ];

        $task->update([
            'status' => $statusMap[$request->action],
            'admin_note' => $request->admin_note,
            'reviewed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Review tugas berhasil disimpan',
            'data' => $task->load('user:id,name,email'),
        ]);
    }
}
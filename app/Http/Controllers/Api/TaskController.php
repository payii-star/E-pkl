<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use ZipArchive;

class TaskController extends Controller
{
    // GET /intern/tasks -> tugas milik user yang sedang login
    public function index(Request $request)
    {
        $user = $request->user();

        $tasks = Task::where('user_id', $user->id)
            ->with(['creator:id,name', 'attachments'])
            ->orderByRaw("FIELD(status, 'revisi', 'sedang', 'belum', 'submitted', 'selesai', 'ditolak')")
            ->orderBy('due_date')
            ->get();

        return response()->json(['data' => $tasks]);
    }

    // GET /admin/tasks -> semua tugas yang pernah diberikan (buat hr-admin)
    public function adminIndex(Request $request)
    {
        $tasks = Task::with(['user:id,name,email', 'creator:id,name', 'attachments'])
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
        foreach ($task->attachments as $att) {
            Storage::disk('public')->delete($att->path);
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

    // POST /tasks/{task}/submit -> intern mengumpulkan tugas (banyak file/gambar sekaligus + catatan)
    public function submit(Request $request, Task $task)
    {
        $user = $request->user();
        if ($task->user_id !== $user->id) {
            return response()->json(['message' => 'Tidak diizinkan'], 403);
        }

        if ($task->status === 'selesai') {
            return response()->json(['message' => 'Tugas ini sudah selesai, tidak bisa dikumpulkan lagi'], 422);
        }

        $validator = Validator::make($request->all(), [
            'submission_note' => 'nullable|string',
            'attachments' => 'required|array|min:1',
            'attachments.*' => 'file|mimes:jpeg,png,jpg,webp,pdf,doc,docx,xls,xlsx,zip|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        // Hapus lampiran lama kalau ini pengumpulan ulang (setelah revisi/ditolak)
        foreach ($task->attachments as $old) {
            Storage::disk('public')->delete($old->path);
            $old->delete();
        }

        foreach ($request->file('attachments') as $file) {
            $path = $file->store('tasks/submissions', 'public');
            $task->attachments()->create([
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
            ]);
        }

        $task->update([
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
            'data' => $task->load('attachments'),
        ]);
    }

    // GET /tasks/{task}/attachments/zip -> download semua lampiran tugas sebagai 1 file ZIP
    public function downloadAttachmentsZip(Request $request, Task $task)
    {
        $user = $request->user();
        $isOwner = $task->user_id === $user->id;
        $isAdmin = $user->hasPermissionTo('task-management');

        if (!$isOwner && !$isAdmin) {
            return response()->json(['message' => 'Tidak diizinkan'], 403);
        }

        $attachments = $task->attachments;
        if ($attachments->isEmpty()) {
            return response()->json(['message' => 'Tidak ada lampiran untuk tugas ini'], 404);
        }

        $tmpDir = storage_path('app/tmp');
        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, 0755, true);
        }

        $zipFileName = 'tugas-' . $task->id . '-lampiran-' . time() . '.zip';
        $zipPath = $tmpDir . '/' . $zipFileName;

        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $usedNames = [];
        foreach ($attachments as $att) {
            $fullPath = Storage::disk('public')->path($att->path);
            if (!file_exists($fullPath)) {
                continue;
            }

            $name = $att->original_name ?: basename($att->path);
            // Hindari nama file bentrok di dalam zip
            if (isset($usedNames[$name])) {
                $usedNames[$name]++;
                $ext = pathinfo($name, PATHINFO_EXTENSION);
                $base = pathinfo($name, PATHINFO_FILENAME);
                $name = $base . '-' . $usedNames[$name] . ($ext ? ".{$ext}" : '');
            } else {
                $usedNames[$name] = 0;
            }

            $zip->addFile($fullPath, $name);
        }

        $zip->close();

        return response()->download($zipPath, 'tugas-' . $task->id . '-lampiran.zip')
            ->deleteFileAfterSend(true);
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
            'data' => $task->load(['user:id,name,email', 'attachments']),
        ]);
    }
}
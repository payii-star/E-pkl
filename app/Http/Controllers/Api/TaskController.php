<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TaskController extends Controller
{
    // GET /intern/tasks -> tugas milik user yang sedang login
    public function index(Request $request)
    {
        $user = $request->user();

        $tasks = Task::where('user_id', $user->id)
            ->orderByRaw("FIELD(status, 'belum', 'sedang', 'selesai')")
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

    // POST /tasks / POST /admin/tasks -> admin/pembimbing memberi tugas ke user tertentu
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
        $task->delete();

        return response()->json(['message' => 'Tugas berhasil dihapus']);
    }

    // PATCH /tasks/{task}/status -> user update progress tugasnya sendiri
    public function updateStatus(Request $request, Task $task)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:belum,sedang,selesai',
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
}
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TaskController extends Controller
{
    // GET /intern/tasks -> tugas milik intern yang sedang login
    public function index(Request $request)
    {
        $intern = $request->user()->intern;

        if (!$intern) {
            return response()->json(['data' => []]);
        }

        $tasks = Task::where('intern_id', $intern->id)
            ->orderByRaw("FIELD(status, 'belum', 'sedang', 'selesai')")
            ->orderBy('due_date')
            ->get();

        return response()->json(['data' => $tasks]);
    }

    // POST /tasks -> admin/pembimbing memberi tugas ke intern tertentu
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'intern_id' => 'required|exists:interns,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $task = Task::create([
            'intern_id' => $request->intern_id,
            'created_by' => $request->user()->id,
            'title' => $request->title,
            'description' => $request->description,
            'due_date' => $request->due_date,
            'status' => 'belum',
        ]);

        return response()->json(['data' => $task], 201);
    }

    // PATCH /tasks/{task}/status -> intern update progress tugasnya sendiri
    public function updateStatus(Request $request, Task $task)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:belum,sedang,selesai',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $intern = $request->user()->intern;
        if (!$intern || $task->intern_id !== $intern->id) {
            return response()->json(['message' => 'Tidak diizinkan'], 403);
        }

        $task->update(['status' => $request->status]);

        return response()->json(['data' => $task]);
    }
}
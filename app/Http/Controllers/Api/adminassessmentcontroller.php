<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DisciplineRecord;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Fitur Rekap & Penilaian Siswa Magang.
 *
 * - Admin merekap pelanggaran/kedisiplinan harian (telat, pulang cepat,
 *   tanpa keterangan, tidak mengerjakan tugas) atau memberi nilai tambahan
 *   (bonus) lewat DisciplineRecord.
 * - Poin dari progres tugas dihitung otomatis dari tabel tasks yang sudah
 *   ada (tidak ada tabel baru khusus tugas) — tugas yang lewat deadline dan
 *   belum selesai kena potongan, yang "selesai" tidak kena potongan sama
 *   sekali, dan kecepatan submit (submitted_at vs due_date) direkap untuk
 *   melihat siswa yang mengumpulkan lebih cepat/rajin.
 * - Nilai akhir = 100 (base) + total poin disiplin + total poin tugas,
 *   dibatasi antara 0-100.
 */
class AdminAssessmentController extends Controller
{
    private const BASE_SCORE = 100;

    private const DEFAULT_POINTS = [
        'telat' => -5,
        'pulang_cepat' => -5,
        'tanpa_keterangan' => -10,
        'tidak_kerjakan_tugas' => -10,
        'bonus' => 5,
        'lainnya' => 0,
    ];

    // Status tugas yang dianggap "belum dikerjakan" kalau sudah lewat deadline
    private const UNFINISHED_TASK_STATUSES = ['belum', 'sedang', 'revisi', 'ditolak'];

    // GET /admin/assessments?start=&end= -> ringkasan semua peserta magang dalam 1 periode
    public function index(Request $request)
    {
        [$start, $end] = $this->resolvePeriod($request);

        $interns = User::query()
            ->whereDoesntHave('roles', function ($query) {
                $query->whereIn('name', ['hr-admin', 'atasan']);
            })
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'photo']);

        $data = $interns->map(fn ($intern) => $this->buildSummary($intern, $start, $end));

        return response()->json([
            'data' => $data,
            'period' => [
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
            ],
        ]);
    }

    // GET /admin/assessments/{user}?start=&end= -> detail rekap + penilaian 1 peserta magang
    public function show(Request $request, User $user)
    {
        [$start, $end] = $this->resolvePeriod($request);

        $summary = $this->buildSummary($user, $start, $end, withDetail: true);

        return response()->json(['data' => $summary]);
    }

    // POST /admin/assessments/{user}/records -> admin menambah catatan rekap harian
    public function storeRecord(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
            'type' => 'required|in:telat,pulang_cepat,tanpa_keterangan,tidak_kerjakan_tugas,bonus,lainnya',
            'points' => 'nullable|integer|min:-100|max:100',
            'note' => 'nullable|string|max:1000',
            'task_id' => 'nullable|exists:tasks,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $points = $request->filled('points') ? (int) $request->points : self::DEFAULT_POINTS[$request->type];

        $record = DisciplineRecord::create([
            'user_id' => $user->id,
            'date' => $request->date,
            'type' => $request->type,
            'points' => $points,
            'note' => $request->note,
            'task_id' => $request->task_id,
            'created_by' => $request->user()->id,
        ]);

        return response()->json([
            'data' => $record->load('creator:id,name'),
            'message' => 'Catatan rekap berhasil ditambahkan',
        ], 201);
    }

    // PUT /admin/assessments/records/{record} -> admin edit catatan rekap
    public function updateRecord(Request $request, DisciplineRecord $record)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
            'type' => 'required|in:telat,pulang_cepat,tanpa_keterangan,tidak_kerjakan_tugas,bonus,lainnya',
            'points' => 'nullable|integer|min:-100|max:100',
            'note' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $points = $request->filled('points') ? (int) $request->points : self::DEFAULT_POINTS[$request->type];

        $record->update([
            'date' => $request->date,
            'type' => $request->type,
            'points' => $points,
            'note' => $request->note,
        ]);

        return response()->json([
            'data' => $record->fresh()->load('creator:id,name'),
            'message' => 'Catatan rekap berhasil diperbarui',
        ]);
    }

    // DELETE /admin/assessments/records/{record} -> admin hapus catatan rekap
    public function destroyRecord(DisciplineRecord $record)
    {
        $record->delete();

        return response()->json(['message' => 'Catatan rekap berhasil dihapus']);
    }

    // ------------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------------

    private function resolvePeriod(Request $request): array
    {
        $start = $request->filled('start')
            ? Carbon::parse($request->start)->startOfDay()
            : now()->startOfWeek();

        $end = $request->filled('end')
            ? Carbon::parse($request->end)->endOfDay()
            : now()->endOfWeek();

        return [$start, $end];
    }

    private function buildSummary(User $intern, Carbon $start, Carbon $end, bool $withDetail = false): array
    {
        $records = DisciplineRecord::where('user_id', $intern->id)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->with('creator:id,name')
            ->orderBy('date')
            ->get();

        $disciplinePoints = (int) $records->sum('points');

        $tasks = Task::where('user_id', $intern->id)
            ->whereBetween('due_date', [$start->toDateString(), $end->toDateString()])
            ->get();

        $totalTasks = $tasks->count();
        $completedTasks = $tasks->where('status', 'selesai')->count();

        $overdueUnfinished = $tasks
            ->whereIn('status', self::UNFINISHED_TASK_STATUSES)
            ->filter(fn ($t) => $t->due_date && Carbon::parse($t->due_date)->endOfDay()->isPast());

        $notCompletedTasks = $overdueUnfinished->count();
        $taskPoints = $notCompletedTasks * self::DEFAULT_POINTS['tidak_kerjakan_tugas'];

        // Rata-rata selisih submit vs deadline dalam jam.
        // Positif = dikumpulkan lebih cepat dari deadline, negatif = terlambat.
        $submittedTasks = $tasks->filter(fn ($t) => $t->submitted_at && $t->due_date);
        $avgSubmissionDeltaHours = null;

        if ($submittedTasks->isNotEmpty()) {
            $deltas = $submittedTasks->map(function ($t) {
                $due = Carbon::parse($t->due_date)->endOfDay();
                $submitted = Carbon::parse($t->submitted_at);

                return ($due->timestamp - $submitted->timestamp) / 3600;
            });

            $avgSubmissionDeltaHours = round($deltas->avg(), 1);
        }

        $finalScore = max(0, min(100, self::BASE_SCORE + $disciplinePoints + $taskPoints));

        $result = [
            'user' => $intern->only(['id', 'name', 'email', 'photo']),
            'base_score' => self::BASE_SCORE,
            'discipline_points' => $disciplinePoints,
            'task_points' => $taskPoints,
            'final_score' => $finalScore,
            'task_summary' => [
                'total' => $totalTasks,
                'completed' => $completedTasks,
                'not_completed' => $notCompletedTasks,
                'avg_submission_delta_hours' => $avgSubmissionDeltaHours,
            ],
        ];

        if ($withDetail) {
            $result['records'] = $records;
            $result['tasks'] = $tasks->map(fn ($t) => [
                'id' => $t->id,
                'title' => $t->title,
                'status' => $t->status,
                'due_date' => $t->due_date,
                'submitted_at' => $t->submitted_at,
            ])->values();
        }

        return $result;
    }
}
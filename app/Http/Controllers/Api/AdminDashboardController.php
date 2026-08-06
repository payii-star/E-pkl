<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Journal;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    public function summary(Request $request)
    {
        $adminId = $request->user()->id;
        $visibleUsers = User::query()
            ->whereDoesntHave('roles', function ($query) {
                $query->whereIn('name', ['hr-admin', 'atasan']);
            });

        return response()->json([
            'total_interns' => $visibleUsers->count(),
            'pending_journals' => Journal::whereHas('user', function ($query) use ($adminId) {
                $query->where('atasan_id', $adminId);
            })->where('status', 'pending')->count(),
            'total_tasks_given' => Task::where('created_by', $adminId)->count(),
        ]);
    }
}
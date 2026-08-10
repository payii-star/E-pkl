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

        // If admin/hr-admin, show total pending journals across all users
        $user = $request->user();
        if (method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['admin', 'hr-admin'])) {
            $pending = Journal::where('status', 'pending')->count();
        } else {
            $pending = Journal::whereHas('user', function ($query) use ($adminId) {
                $query->where('atasan_id', $adminId);
            })->where('status', 'pending')->count();
        }

        return response()->json([
            'total_interns' => $visibleUsers->count(),
            'pending_journals' => $pending,
            'total_tasks_given' => Task::where('created_by', $adminId)->count(),
        ]);
    }
}
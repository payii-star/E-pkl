<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Intern;
use App\Models\Journal;
use App\Models\Task;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    public function summary(Request $request)
    {
        $adminId = $request->user()->id;

        return response()->json([
            'total_interns' => Intern::count(),
            'pending_journals' => Journal::whereHas('intern', function ($query) use ($adminId) {
                $query->where('pembimbing_id', $adminId);
            })->where('status', 'pending')->count(),
            'total_tasks_given' => Task::where('created_by', $adminId)->count(),
        ]);
    }
}
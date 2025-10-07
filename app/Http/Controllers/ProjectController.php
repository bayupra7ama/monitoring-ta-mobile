<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Project;
use Illuminate\Http\Request;
use App\Helpers\ResponseFormatter;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $projects = Project::where('user_id', $request->user()->id)->get();
        return ResponseFormatter::success($projects, 'Daftar project milik user');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $project = Project::create([
            'user_id' => $request->user()->id,
            'title' => $request->title,
            'description' => $request->description,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
        ]);

        return ResponseFormatter::success($project, 'Project berhasil dibuat');
    }

    public function show($id, Request $request)
    {
        $project = Project::with('tasks.subtasks')->find($id);

        if (!$project) {
            return ResponseFormatter::error(null, 'Project tidak ditemukan', 404);
        }

        if ($request->user()->id != $project->user_id) {
            return ResponseFormatter::error([
                'login_id' => $request->user()->id,
                'owner_id' => $project->user_id,
            ], 'Akses ditolak', 403);
        }

        // BURNDOWN LOGIC (FIXED: Tanpa Titik '0' di Label X)
        $start = Carbon::parse($project->start_date);
        $end = Carbon::parse($project->end_date);
        $days = $start->diffInDays($end) + 1;

        $totalTasks = $project->tasks->count(); // Total Tugas (misal: 4)
        $weeks = ceil($days / 7); // Jumlah periode/minggu (misal: 5)

        // 🚨 Penting: Kita buat array data memiliki (weeks + 1) titik data.
        // Titik pertama akan menjadi titik "START" yang kita tempatkan di Minggu 1.

        // 1. DATA ACTUAL
        $weeklyActual = [$totalTasks]; // Titik awal: Total Tugas (4)

        for ($w = 0; $w < $weeks; $w++) {
            $weekStart = $start->copy()->addDays($w * 7);
            $weekEnd = $weekStart->copy()->addDays(6);

            $cutoff = $weekEnd->lt($end) ? $weekEnd : $end;

            // Hitung tugas yang sudah selesai sampai tanggal cutoff
            $doneTasks = $project->tasks->filter(function ($task) use ($cutoff) {
                return $task->status === 'selesai' &&
                    Carbon::parse($task->updated_at)->lte($cutoff);
            })->count();

            $remaining = $totalTasks - $doneTasks; // Sisa Tugas
            $weeklyActual[] = $remaining;
        }

        // 2. DATA IDEAL
        $ideal = [];
        $totalPoints = $weeks + 1; // Total titik data
        $step = $totalTasks / $weeks;

        for ($i = 0; $i < $totalPoints; $i++) {
            $ideal[] = max(round($totalTasks - ($i * $step)), 0);
        }

        // 3. LABELS (Hanya Label Minggu, tidak ada '0')
        $weeklyLabels = [];
        for ($w = 1; $w <= $weeks; $w++) {
            $weeklyLabels[] = (string)$w;
        }

        // 4. RESPONSE
        return ResponseFormatter::success([
            'project' => $project,
            'burndown' => [
                'labels' => $weeklyLabels, // Misal: ["1", "2", "3", "4", "5"] -> 5 elemen
                'actual' => $weeklyActual, // Misal: [4, 2, 2, 2, 2, 2] -> 6 elemen
                'ideal' => $ideal,         // Misal: [4, 3, 2, 1, 0] -> 6 elemen
            ]
        ], 'Detail project mahasiswa');
    }



    public function update(Request $request, $id)
    {
        $project = Project::find($id);

        if (!$project) {
            return ResponseFormatter::error(null, 'Project tidak ditemukan', 404);
        }

        if ($request->user()->id != $project->user_id) {
            return ResponseFormatter::error([
                'login_id' => $request->user()->id,
                'owner_id' => $project->user_id,
            ], 'Akses ditolak', 403);
        }

        $request->validate([
            'title' => 'sometimes|required|string',
            'description' => 'nullable|string',
            'start_date' => 'sometimes|required|date',
            'end_date' => 'sometimes|required|date|after_or_equal:start_date',
        ]);

        $project->update($request->only('title', 'description', 'start_date', 'end_date'));

        return ResponseFormatter::success($project, 'Project berhasil diperbarui');
    }


    public function destroy(Request $request, Project $project)
    {
        if ($request->user()->id !== $project->user_id) {
            return ResponseFormatter::error(null, 'Akses ditolak', 403);
        }

        $project->delete();
        return ResponseFormatter::success(null, 'Project berhasil dihapus');
    }

    public function burndown($id)
    {
        $project = Project::with('tasks')->find($id);

        if (!$project || $project->user_id !== auth()->id()) {
            return ResponseFormatter::error(null, 'Project tidak ditemukan atau akses ditolak', 403);
        }

        $start = Carbon::parse($project->start_date);
        $end = Carbon::parse($project->end_date);
        $tasks = $project->tasks;

        $dates = [];
        $actual = [];

        $days = $start->diffInDays($end) + 1;

        $totalTasks = $tasks->count();

        for ($i = 0; $i < $days; $i++) {
            $currentDate = $start->copy()->addDays($i)->toDateString();

            // hitung yang sudah selesai sampai tanggal itu
            $doneTasks = $tasks->filter(function ($task) use ($currentDate) {
                return $task->status === 'selesai' &&
                    Carbon::parse($task->updated_at)->lte($currentDate);
            })->count();

            $remaining = $totalTasks - $doneTasks;

            $dates[] = $currentDate;
            $actual[] = $remaining;
        }

        return ResponseFormatter::success([
            'labels' => $dates,
            'actual' => $actual
        ], 'Burndown chart data');
    }
}

<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Project;
use Illuminate\Http\Request;
use App\Helpers\ResponseFormatter;

class DosenController extends Controller
{
    //
    public function mahasiswaBimbingan(Request $request)
    {
        $dosen = $request->user(); // dosen yang login

        $mahasiswa = User::where('dosen_id', $dosen->id)
            ->select('id', 'name', 'email', 'nim_nidn', 'jurusan', 'prodi', 'photo')
            ->get();

        return ResponseFormatter::success($mahasiswa, 'Daftar mahasiswa bimbingan');
    }

    public function projectsByMahasiswa($id, Request $request)
    {
        $dosen = $request->user();

        // cek apakah mahasiswa itu benar bimbingannya
        $mahasiswa = User::where('id', $id)->where('dosen_id', $dosen->id)->first();
        if (!$mahasiswa) {
            return ResponseFormatter::error(null, 'Mahasiswa tidak ditemukan atau bukan bimbingan Anda', 404);
        }

        $projects = Project::where('user_id', $mahasiswa->id)
            ->select('id', 'title', 'description', 'start_date', 'end_date')
            ->get();

        return ResponseFormatter::success($projects, 'Daftar project mahasiswa');
    }
    public function projectDashboard($id, Request $request)
    {
        $project = Project::with('tasks.subtasks')->find($id);

        if (!$project) {
            return ResponseFormatter::error(null, 'Project tidak ditemukan', 404);
        }

        // Validasi dosen pembimbing
        $mahasiswa = User::where('id', $project->user_id)
            ->where('dosen_id', $request->user()->id)
            ->first();

        if (!$mahasiswa) {
            return ResponseFormatter::error(null, 'Akses ditolak', 403);
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


        return ResponseFormatter::success([
            'project' => $project,
            'burndown' => [
                'labels' => $weeklyLabels,
                'actual' => $weeklyActual,
                'ideal' => $ideal,
            ]
        ], 'Dashboard project mahasiswa');
    }
}

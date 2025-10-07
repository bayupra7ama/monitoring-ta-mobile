<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;
use App\Models\ProgressReport;
use App\Helpers\ResponseFormatter;
use Illuminate\Support\Facades\Storage;
use App\Notifications\ReportValidatedNotification;
use App\Notifications\LaporanBaruDiuploadNotification;

class ProgressReportController extends Controller
{
    // Mahasiswa upload laporan
    public function store($taskId, Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf,docx|max:5120'
        ]);

        $task = Task::find($taskId);
        if (!$task || $task->project->user_id != $request->user()->id) {
            return ResponseFormatter::error(null, 'Akses ditolak atau task tidak ditemukan', 403);
        }

        $path = $request->file('file')->store('reports', 'public');

        $report = ProgressReport::create([
            'task_id' => $taskId,
            'user_id' => $request->user()->id,
            'file_path' => $path,
            'status_validasi' => 'pending'
        ]);

        // ✅ Kirim notifikasi ke dosen pembimbing
        $mahasiswa = $request->user();
        $dosen = $mahasiswa->dosen; // relasi dosen

        if ($dosen) {
            $dosen->notify(new LaporanBaruDiuploadNotification($mahasiswa, $task, $report));
        }

        return ResponseFormatter::success($report, 'Laporan berhasil diupload');
    }
    // Mahasiswa lihat laporan mereka
    public function myReports(Request $request)
    {
        $reports = ProgressReport::with([
            'task.subtasks'
        ])->where('user_id', $request->user()->id)
            ->latest() // <--- Tambahkan ini untuk mengurutkan dari yang terbaru
            ->get();

        return ResponseFormatter::success($reports, 'Daftar laporan kamu');
    }

    //Dosen lihat semua laporan


    public function all(Request $request)
    {
        $dosenId = $request->user()->id;

        $reports = ProgressReport::whereHas('user', function ($q) use ($dosenId) {
            $q->where('dosen_id', $dosenId);
        })->with('user', 'task')
            ->latest() // <--- Tambahkan ini untuk mengurutkan dari yang terbaru
            ->get();

        return ResponseFormatter::success($reports, 'Daftar laporan mahasiswa bimbingan Anda');
    }
    public function validateReport($id, Request $request)
    {
        $request->validate([
            'status_validasi' => 'required|in:disetujui,ditolak,pending', // <--- PERBAIKAN DI SINI
            'feedback' => 'nullable|string'
        ]);

        $report = ProgressReport::find($id);
        if (!$report) {
            return ResponseFormatter::error(null, 'Laporan tidak ditemukan', 404);
        }

        $report->update([
            'status_validasi' => $request->status_validasi,
            'feedback' => $request->feedback
        ]);

        $report->load(['user', 'task']); // Muat relasi user dan task

        // === LOGIKA UNTUK UPDATE STATUS TASK (tetap sama, sudah benar) ===
        if ($report->task) { // Pastikan ada task yang terkait
            if ($request->status_validasi === 'disetujui') {
                $report->task->update(['status' => 'selesai']);
            } elseif ($request->status_validasi === 'ditolak') {
                $report->task->update(['status' => 'belum']);
            } elseif ($request->status_validasi === 'pending') { // Ini sudah benar
                \Log::info("Mencoba update Task {$report->task->id} status ke 'proses' karena revisi.");

                $report->task->update(['status' => 'proses']); // Atau status lain yang sesuai
            }
        } else {
            \Log::warning("Task untuk laporan ID {$report->id} tidak ditemukan.");
        }
        // ================================================================

        // === PERBAIKAN DI SINI: Pindahkan notifikasi dan response keluar dari blok if/else task ===
        // Kirim notifikasi ke user (ini harus selalu dijalankan jika report ditemukan)
        if ($report->user) {
            $report->user->notify(new ReportValidatedNotification(
                $request->status_validasi,
                $request->feedback,
                $report->id
            ));
        } else {
            \Log::error('Relasi user NULL untuk report ID: ' . $report->id);
        }

        // Kembalikan respons sukses (ini harus selalu dijalankan)
        return ResponseFormatter::success($report, 'Laporan berhasil divalidasi');
    }


    public function show($id, Request $request)
    {
        $user = $request->user();

        // Mengambil laporan beserta relasi user, task, project, dan subtasks
        $report = ProgressReport::with([
            'user',
            'task.project',
            'task.subtasks' // tambahkan relasi subtasks
        ])->find($id);

        if (!$report) {
            return ResponseFormatter::error(null, 'Laporan tidak ditemukan', 404);
        }

        // Validasi akses laporan
        if ($user->role === 'dosen' && $report->user->dosen_id !== $user->id) {
            return ResponseFormatter::error(null, 'Akses ditolak', 403);
        }

        if ($user->role === 'mahasiswa' && $report->user_id !== $user->id) {
            return ResponseFormatter::error(null, 'Akses ditolak', 403);
        }

        return ResponseFormatter::success($report, 'Detail laporan ditemukan');
    }
}

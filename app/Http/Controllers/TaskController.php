<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseFormatter;
use App\Models\Task;
use App\Models\Project;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index($projectId, Request $request)
    {
        $project = Project::find($projectId);

        if (!$project || $project->user_id != $request->user()->id) {
            return ResponseFormatter::error(null, 'Akses ditolak atau project tidak ditemukan', 403);
        }

        $tasks = Task::where('project_id', $projectId)->get();
        return ResponseFormatter::success($tasks, 'Daftar task');
    }

    public function store($projectId, Request $request)
    {
        $project = Project::find($projectId);

        if (!$project || $project->user_id != $request->user()->id) {
            return ResponseFormatter::error(null, 'Akses ditolak atau project tidak ditemukan', 403);
        }

        $request->validate([
            'title' => 'required|string',
            'status' => 'in:belum,proses,selesai',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date'
        ]);

        $task = Task::create([
            'project_id' => $projectId,
            'title' => $request->title,
            'status' => $request->status ?? 'belum',
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
        ]);

        return ResponseFormatter::success($task, 'Task berhasil dibuat');
    }

    public function show($id, Request $request)
    {
    $task = Task::with('subtasks')->find($id);

        if (!$task || $task->project->user_id != $request->user()->id) {
            return ResponseFormatter::error(null, 'Akses ditolak atau task tidak ditemukan', 403);
        }

        return ResponseFormatter::success($task, 'Detail task');
    }

    public function update($id, Request $request)
    {
        $task = Task::find($id);

        if (!$task || $task->project->user_id != $request->user()->id) {
            return ResponseFormatter::error(null, 'Akses ditolak atau task tidak ditemukan', 403);
        }

        $request->validate([
            'title' => 'sometimes|required|string',
            'status' => 'sometimes|required|in:belum,proses,selesai',
            'start_date' => 'sometimes|required|date',
            'end_date' => 'sometimes|required|date|after_or_equal:start_date'
        ]);

        $task->update($request->only('title', 'status', 'start_date', 'end_date'));

        return ResponseFormatter::success($task, 'Task berhasil diperbarui');
    }

    public function destroy($id, Request $request)
    {
        $task = Task::find($id);

        if (!$task || $task->project->user_id != $request->user()->id) {
            return ResponseFormatter::error(null, 'Akses ditolak atau task tidak ditemukan', 403);
        }

        $task->delete();

        return ResponseFormatter::success(null, 'Task berhasil dihapus');
    }
}

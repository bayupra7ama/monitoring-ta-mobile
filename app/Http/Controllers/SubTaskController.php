<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseFormatter;
use App\Models\SubTask;
use App\Models\Task;
use Illuminate\Http\Request;

class SubTaskController extends Controller
{
    public function index($taskId, Request $request)
    {
        $task = Task::find($taskId);

        if (!$task || $task->project->user_id != $request->user()->id) {
            return ResponseFormatter::error(null, 'Akses ditolak atau task tidak ditemukan', 403);
        }

        $subTasks = SubTask::where('task_id', $taskId)->get();
        return ResponseFormatter::success($subTasks, 'Daftar subtask');
    }

    public function store($taskId, Request $request)
    {
        $task = Task::find($taskId);

        if (!$task || $task->project->user_id != $request->user()->id) {
            return ResponseFormatter::error(null, 'Akses ditolak atau task tidak ditemukan', 403);
        }

        $request->validate([
            'title' => 'required|string',
            'status' => 'in:belum,proses,selesai'
        ]);

        $subTask = SubTask::create([
            'task_id' => $taskId,
            'title' => $request->title,
            'status' => $request->status ?? 'belum',
        ]);

        return ResponseFormatter::success($subTask, 'Subtask berhasil dibuat');
    }

    public function update($id, Request $request)
    {
        $subTask = SubTask::find($id);

        if (!$subTask || $subTask->task->project->user_id != $request->user()->id) {
            return ResponseFormatter::error(null, 'Akses ditolak atau subtask tidak ditemukan', 403);
        }

        $request->validate([
            'title' => 'sometimes|required|string',
            'status' => 'sometimes|required|in:belum,proses,selesai'
        ]);

        $subTask->update($request->only('title', 'status'));

        return ResponseFormatter::success($subTask, 'Subtask berhasil diperbarui');
    }

    public function destroy($id, Request $request)
    {
        $subTask = SubTask::find($id);

        if (!$subTask || $subTask->task->project->user_id != $request->user()->id) {
            return ResponseFormatter::error(null, 'Akses ditolak atau subtask tidak ditemukan', 403);
        }

        $subTask->delete();
        return ResponseFormatter::success(null, 'Subtask berhasil dihapus');
    }
}

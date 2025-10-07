<?php

use Illuminate\Http\Request;
use App\Helpers\ResponseFormatter;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DosenController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\SubTaskController;
use App\Http\Controllers\ProgressReportController;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
});
Route::middleware(['auth:sanctum', 'check.role:mahasiswa'])->group(function () {
    Route::get('/projects', [ProjectController::class, 'index']);    // lihat semua project milik sendiri
    Route::post('/projects', [ProjectController::class, 'store']);    // buat project
    Route::get('/projects/{id}', [ProjectController::class, 'show']); // detail
    Route::put('/projects/{id}', [ProjectController::class, 'update']); // edit
    Route::delete('/projects/{id}', [ProjectController::class, 'destroy']); // hapus

    //task
    Route::get('/projects/{projectId}/tasks', [TaskController::class, 'index']);
    Route::post('/projects/{projectId}/tasks', [TaskController::class, 'store']);
    Route::get('/tasks/{id}', [TaskController::class, 'show']);
    Route::put('/tasks/{id}', [TaskController::class, 'update']);
    Route::delete('/tasks/{id}', [TaskController::class, 'destroy']);

    Route::get('/tasks/{taskId}/subtasks', [SubTaskController::class, 'index']);
    
    Route::post('/tasks/{taskId}/subtasks', [SubTaskController::class, 'store']);
    Route::put('/subtasks/{id}', [SubTaskController::class, 'update']);
    Route::delete('/subtasks/{id}', [SubTaskController::class, 'destroy']);

    Route::post('/tasks/{taskId}/reports', [ProgressReportController::class, 'store']);
    Route::get('/my-reports', [ProgressReportController::class, 'myReports']);

    Route::get('/projects/{id}/burndown', [ProjectController::class, 'burndown']);
});

Route::middleware(['auth:sanctum', 'check.role:dosen'])->group(function () {
    Route::get('/reports', [ProgressReportController::class, 'all']);
    Route::put('/reports/{id}/validate', [ProgressReportController::class, 'validateReport']);
    Route::get('/dosen/mahasiswa', [DosenController::class, 'mahasiswaBimbingan']);
    Route::get('/dosen/mahasiswa/{id}/projects', [DosenController::class, 'projectsByMahasiswa']);
    Route::get('/dosen/projects/{id}/dashboard', [DosenController::class, 'projectDashboard']);
});

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/reports/{id}', [ProgressReportController::class, 'show']);
});

Route::middleware('auth:sanctum')->get('/notifications', function (Request $request) {
    return ResponseFormatter::success($request->user()->notifications, 'Daftar notifikasi');
});


Route::middleware('auth:sanctum')->post('/save-token', function (Request $request) {
    $request->validate([
        'token' => 'required|string',
    ]);

    $user = $request->user();
    $user->fcm_token = $request->input('token');
    $user->save();

    return response()->json(['success' => true]);
});

Route::middleware('auth:sanctum')->get('/notifications/unread', function (Request $request) {
    return ResponseFormatter::success(
        $request->user()->unreadNotifications,
        'Notifikasi belum dibaca'
    );
});
Route::middleware('auth:sanctum')->post('/notifications/{id}/read', function (Request $request, $id) {
    $notif = $request->user()->notifications()->where('id', $id)->first();

    if ($notif) {
        $notif->markAsRead();
        return ResponseFormatter::success(null, 'Notifikasi ditandai sebagai sudah dibaca');
    }

    return ResponseFormatter::error(null, 'Notifikasi tidak ditemukan', 404);
});
Route::middleware('auth:sanctum')->post('/notifications/read-all', function (Request $request) {
    $request->user()->unreadNotifications->markAsRead();

    return ResponseFormatter::success(null, 'Semua notifikasi ditandai sebagai sudah dibaca');
});

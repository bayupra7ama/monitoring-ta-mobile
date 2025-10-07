<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;



namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LaporanBaruDiuploadNotification extends Notification
{
    use Queueable;

    protected $mahasiswa;
    protected $task;
    protected $report;

    public function __construct($mahasiswa, $task, $report)
    {
        $this->mahasiswa = $mahasiswa;
        $this->task = $task;
        $this->report = $report;
    }

    // ✅ WAJIB ADA
    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'title' => 'Laporan baru diupload',
            'message' => "{$this->mahasiswa->name} mengunggah laporan untuk tugas \"{$this->task->title}\"",
            'status' => 'baru',
            'report_id' => $this->report->id,
            'created_at' => now()
        ];
    }
}

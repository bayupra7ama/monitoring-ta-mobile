<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ReportValidatedNotification extends Notification
{
    use Queueable;

    private $status;
    private $feedback;
    private $reportId;

    public function __construct($status, $feedback, $reportId)
    {
        $this->status = $status;
        $this->feedback = $feedback;
        $this->reportId = $reportId;
    }

    // ✅ WAJIB ADA
    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'title' => 'Laporan Anda telah divalidasi',
            'message' => "Status: {$this->status}" . ($this->feedback ? " - Feedback: {$this->feedback}" : ''),
            'status' => $this->status,
            'report_id' => $this->reportId,
            'created_at' => now()
        ];
    }
}

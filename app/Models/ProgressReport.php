<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class ProgressReport extends Model
{
    protected $fillable = [
        'task_id', 'user_id', 'file_path', 'status_validasi', 'feedback'
    ];
    

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    protected $appends = ['file_url'];

    public function getFileUrlAttribute()
    {
        return $this->file_path ? asset('storage/' . $this->file_path) : null;
    }
}

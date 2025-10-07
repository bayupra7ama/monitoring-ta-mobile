<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    protected $fillable = [
        'project_id',
        'title',
        'status',
        'start_date',
        'end_date'
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
    public function subTasks()
    {
        return $this->hasMany(SubTask::class);
    }
}

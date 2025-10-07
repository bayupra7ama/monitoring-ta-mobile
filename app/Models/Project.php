<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'description',
        'start_date',
        'end_date'
    ];
    public function tasks()
    {
        return $this->hasMany(\App\Models\Task::class);
    }
}

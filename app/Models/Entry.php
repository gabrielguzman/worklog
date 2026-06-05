<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Entry extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'project_id', 'title', 'content',
        'type', 'entry_date', 'entry_time', 'is_pinned',
    ];

    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
            'is_pinned'  => 'boolean',
        ];
    }

    public function user()        { return $this->belongsTo(User::class); }
    public function project()     { return $this->belongsTo(Project::class); }
    public function tags()        { return $this->belongsToMany(Tag::class); }
    public function tasks()       { return $this->hasMany(Task::class); }
    public function attachments() { return $this->morphMany(Attachment::class, 'attachable'); }
}

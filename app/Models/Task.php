<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'project_id', 'entry_id', 'parent_task_id', 'title', 'description',
        'priority', 'status', 'due_date', 'completed_at', 'sort_order',
        'recurrence_type', 'recurrence_interval', 'recurrence_ends_at',
    ];

    protected function casts(): array
    {
        return [
            'due_date'          => 'date',
            'completed_at'      => 'datetime',
            'recurrence_ends_at' => 'date',
        ];
    }

    public function user()          { return $this->belongsTo(User::class); }
    public function project()       { return $this->belongsTo(Project::class); }
    public function entry()         { return $this->belongsTo(Entry::class); }
    public function parentTask()    { return $this->belongsTo(Task::class, 'parent_task_id'); }
    public function tags()          { return $this->belongsToMany(Tag::class, 'task_tag'); }
    public function attachments()   { return $this->morphMany(Attachment::class, 'attachable'); }
    public function focusSessions() { return $this->hasMany(FocusSession::class); }
    public function subtasks()      { return $this->hasMany(Task::class, 'parent_task_id')->orderBy('sort_order', 'asc')->orderBy('created_at', 'asc'); }
    public function comments()      { return $this->hasMany(TaskComment::class)->orderBy('created_at', 'desc'); }

    public function markDone(): void
    {
        $this->update(['status' => 'done', 'completed_at' => now()]);
    }

    public function subtaskProgress(): array
    {
        $total = $this->subtasks()->count();
        if ($total === 0) return ['done' => 0, 'total' => 0];
        $done = $this->subtasks()->where('status', 'done')->count();
        return ['done' => $done, 'total' => $total];
    }

    public function isRecurrent(): bool
    {
        return $this->recurrence_type !== 'none';
    }

    public function nextDueDate(): ?\DateTime
    {
        if (!$this->due_date || !$this->isRecurrent()) return null;

        return match ($this->recurrence_type) {
            'daily'   => $this->due_date->copy()->addDays($this->recurrence_interval),
            'weekly'  => $this->due_date->copy()->addWeeks($this->recurrence_interval),
            'monthly' => $this->due_date->copy()->addMonths($this->recurrence_interval),
            default   => null,
        };
    }

    public function spawnNextRecurrence(): ?self
    {
        if (!$this->isRecurrent()) return null;

        $nextDue = $this->nextDueDate();
        if (!$nextDue) return null;

        if ($this->recurrence_ends_at && $nextDue->isAfter($this->recurrence_ends_at)) {
            return null;
        }

        $next = $this->replicate(['completed_at', 'sort_order']);
        $next->status       = 'pending';
        $next->due_date     = $nextDue;
        $next->completed_at = null;
        $next->sort_order   = 0;
        $next->save();

        $next->tags()->sync($this->tags()->pluck('tags.id'));

        return $next;
    }
}

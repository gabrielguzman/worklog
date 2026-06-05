<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Project extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'name', 'color', 'description', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function user()    { return $this->belongsTo(User::class); }
    public function entries() { return $this->hasMany(Entry::class); }
    public function tasks()   { return $this->hasMany(Task::class); }
}

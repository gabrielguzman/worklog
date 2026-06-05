<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Template extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'name', 'type', 'fields', 'icon', 'is_active'];

    protected function casts(): array
    {
        return [
            'fields'    => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function user() { return $this->belongsTo(User::class); }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->enum('recurrence_type', ['none', 'daily', 'weekly', 'monthly'])
                  ->default('none')
                  ->after('sort_order');

            $table->unsignedTinyInteger('recurrence_interval')
                  ->default(1)
                  ->after('recurrence_type');

            $table->date('recurrence_ends_at')
                  ->nullable()
                  ->after('recurrence_interval');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn(['recurrence_type', 'recurrence_interval', 'recurrence_ends_at']);
        });
    }
};

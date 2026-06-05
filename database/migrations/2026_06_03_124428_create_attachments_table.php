<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->morphs('attachable'); // attachable_type + attachable_id
            $table->string('filename');           // nombre en disco
            $table->string('original_name');      // nombre original del usuario
            $table->string('mime_type');
            $table->unsignedBigInteger('size');   // bytes
            $table->string('path');               // storage path
            $table->string('disk')->default('local');
            $table->longText('ocr_text')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};

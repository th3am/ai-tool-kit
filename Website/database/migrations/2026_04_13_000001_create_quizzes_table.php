<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quizzes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->enum('source_type', ['text', 'pdf', 'docx', 'pptx'])->default('text');
            $table->longText('source_text');
            $table->enum('status', ['pending', 'processing', 'done', 'failed'])->default('pending');
            $table->string('error_message')->nullable();
            $table->boolean('is_public')->default(false);
            $table->uuid('share_uuid')->unique()->nullable();
            $table->integer('max_questions')->default(5);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quizzes');
    }
};

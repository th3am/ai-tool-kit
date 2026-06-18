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
        // 1. Files / Assets
        if (!Schema::hasTable('files')) {
            Schema::create('files', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
                $table->string('storage_disk')->default('local');
                $table->string('path');
                $table->string('original_name')->nullable();
                $table->string('mime')->nullable();
                $table->unsignedBigInteger('size_bytes')->nullable();
                $table->timestamps();
            });
        }

        // 2. Chat Sessions (The Workspace)
        if (!Schema::hasTable('chat_sessions')) {
            Schema::create('chat_sessions', function (Blueprint $table) {
                $table->uuid('id')->primary(); // UUID
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->string('title')->nullable();
                $table->string('context_type')->nullable(); // 'file', 'text'
                $table->unsignedBigInteger('context_id')->nullable(); 
                $table->timestamps();
            });
        }

        // 3. Tool Jobs (The "Action" History)
        if (!Schema::hasTable('tool_jobs')) {
            Schema::create('tool_jobs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignUuid('chat_session_id')->nullable()->constrained('chat_sessions')->onDelete('cascade'); // UUID FK
                $table->string('tool_type'); // 'mindmap', 'presentation', etc.
                $table->string('status')->default('queued'); // queued, running, succeeded, failed
                $table->json('params')->nullable(); // input text or file ref
                $table->json('results')->nullable(); // output paths, or raw text
                $table->text('error_message')->nullable();
                $table->timestamps();
            });
        } else {
             Schema::table('tool_jobs', function (Blueprint $table) {
                if (!Schema::hasColumn('tool_jobs', 'chat_session_id')) {
                    $table->foreignUuid('chat_session_id')->nullable()->after('user_id')->constrained('chat_sessions')->onDelete('cascade'); // UUID FK
                }
            });
        }

        // 4. Chat Messages (Timeline)
        if (!Schema::hasTable('chat_messages')) {
            Schema::create('chat_messages', function (Blueprint $table) {
                $table->id();
                $table->foreignUuid('session_id')->constrained('chat_sessions')->onDelete('cascade'); // UUID FK
                $table->string('role'); // 'user', 'assistant', 'system'
                $table->longText('content')->nullable();
                $table->json('meta_data')->nullable(); // references to tool_jobs
                $table->unsignedBigInteger('tool_job_id')->nullable(); // if message is from a tool
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tool_jobs', function (Blueprint $table) {
            $table->dropForeign(['chat_session_id']);
            $table->dropColumn('chat_session_id');
        });

        Schema::dropIfExists('chat_messages');
        Schema::dropIfExists('chat_sessions');
    }
};

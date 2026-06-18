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
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // free, premium, enterprise
            $table->string('slug')->unique();
            $table->decimal('price', 8, 2)->default(0);
            $table->integer('credits')->default(0); // For gen-ai usage limits
            $table->json('features')->nullable();
            $table->timestamps();
        });

        Schema::create('otps', function (Blueprint $table) {
            $table->id();
            $table->string('whatsapp_number');
            $table->string('otp_hash');
            $table->string('purpose')->default('login'); // login, register, password_reset
            $table->timestamp('expires_at');
            $table->boolean('is_used')->default(false);
            $table->timestamps();
            
            $table->index(['whatsapp_number', 'is_used']);
        });

        // Add plan_id to users
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('plan_id')->nullable()->constrained('subscription_plans')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['plan_id']);
            $table->dropColumn('plan_id');
        });
        Schema::dropIfExists('otps');
        Schema::dropIfExists('subscription_plans');
    }
};

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
        Schema::table('users', function (Blueprint $table) {
            // Plan reference (already has plan_id in fillable, ensure column exists)
            if (!Schema::hasColumn('users', 'plan_id')) {
                $table->unsignedBigInteger('plan_id')->nullable()->after('role');
                $table->foreign('plan_id')->references('id')->on('subscription_plans')->nullOnDelete();
            }

            // SaaS Credit columns
            if (!Schema::hasColumn('users', 'credits')) {
                $table->integer('credits')->default(50)->after('plan_id');
            }
            if (!Schema::hasColumn('users', 'credits_used')) {
                $table->integer('credits_used')->default(0)->after('credits');
            }
            if (!Schema::hasColumn('users', 'plan_expires_at')) {
                $table->timestamp('plan_expires_at')->nullable()->after('credits_used');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['plan_id']);
            $table->dropColumn(['plan_id', 'credits', 'credits_used', 'plan_expires_at']);
        });
    }
};

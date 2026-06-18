<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('subscription_plans')) {
            Schema::create('subscription_plans', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->decimal('price', 8, 2)->default(0);
                $table->integer('credits')->default(0);
                $table->json('features')->nullable();
                $table->string('color')->default('indigo');
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->integer('sort_order')->default(0);
                $table->timestamps();
            });
        } else {
            // Table exists, add missing columns
            Schema::table('subscription_plans', function (Blueprint $table) {
                if (!Schema::hasColumn('subscription_plans', 'color')) {
                    $table->string('color')->default('indigo')->after('features');
                }
                if (!Schema::hasColumn('subscription_plans', 'description')) {
                    $table->text('description')->nullable()->after('color');
                }
                if (!Schema::hasColumn('subscription_plans', 'is_active')) {
                    $table->boolean('is_active')->default(true)->after('description');
                }
                if (!Schema::hasColumn('subscription_plans', 'sort_order')) {
                    $table->integer('sort_order')->default(0)->after('is_active');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repository_id')->constrained()->cascadeOnDelete();
            $table->string('status');
            $table->unsignedTinyInteger('overall_score')->nullable();
            $table->json('category_scores')->nullable();
            $table->json('summary')->nullable();
            $table->longText('ai_summary')->nullable();
            $table->string('ai_provider')->nullable();
            $table->string('ai_model')->nullable();
            $table->string('github_commit_sha')->nullable();
            $table->timestamps();
            $table->index('created_at');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analyses');
    }
};

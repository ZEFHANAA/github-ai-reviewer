<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repositories', function (Blueprint $table) {
            $table->id();
            $table->string('owner');
            $table->string('name');
            $table->string('full_name');
            $table->string('url');
            $table->text('description')->nullable();
            $table->string('primary_language')->nullable();
            $table->string('default_branch')->nullable();
            $table->unsignedInteger('stars_count')->default(0);
            $table->unsignedInteger('forks_count')->default(0);
            $table->timestamp('github_created_at')->nullable();
            $table->timestamp('github_updated_at')->nullable();
            $table->timestamps();
            $table->unique(['owner', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repositories');
    }
};

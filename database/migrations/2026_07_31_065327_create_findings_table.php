<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('findings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('analysis_id')->constrained()->cascadeOnDelete();
            $table->string('source');
            $table->string('rule_identifier')->nullable();
            $table->string('category');
            $table->string('severity');
            $table->string('status');
            $table->string('title');
            $table->text('message');
            $table->text('evidence')->nullable();
            $table->text('recommendation')->nullable();
            $table->timestamps();
            $table->index('category');
            $table->index('severity');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('findings');
    }
};

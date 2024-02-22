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
        Schema::create('uploaded_files', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('path');
            $table->string('dxf_path')->nullable();
            $table->unsignedInteger('size')->nullable();
            $table->string('uuid')->unique();
            $table->boolean('is_read_done')->default(0);
            $table->json('metadata')->nullable()->default('{}');
            $table->foreignId('task_id')
                ->nullable()
                ->constrained('tasks')
                ->onDelete('cascade');
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('uploaded_files');
    }
};

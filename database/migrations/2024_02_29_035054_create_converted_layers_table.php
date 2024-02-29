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
        Schema::create('converted_layers', function (Blueprint $table) {
            $table->id();
            $table->string('layer_name');
            $table->string('geoserver_ref');
            $table->string('srs');
            $table->json('metadata')->nullable()->default('{}');
            $table->string('uuid')->unique();
            $table->foreignId('task_id')
                ->nullable()
                ->constrained('tasks')
                ->onDelete('cascade');
            $table->foreignId('uploaded_file_id')
                ->nullable()
                ->constrained('uploaded_files')
                ->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('converted_layers');
    }
};

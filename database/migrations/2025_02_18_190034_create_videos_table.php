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
        Schema::create('videos', function (Blueprint $table) {
            $table->id();
            $table->string('token')->unique();
            $table->string('video_path');
            $table->string('segment_path')->nullable();
            $table->string('bucket_path')->nullable();
            $table->longText('hls')->nullable();
            $table->longText('poster')->nullable();
            $table->enum('uploaded',['1','0'])->default('0');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('videos');
    }
};

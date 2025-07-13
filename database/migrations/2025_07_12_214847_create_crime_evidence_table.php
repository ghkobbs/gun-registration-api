<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('crime_evidence', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crime_report_id')->constrained()->cascadeOnDelete();
            $table->string('evidence_type'); // photo, video, audio, document
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('file_path');
            $table->string('file_name');
            $table->string('file_type');
            $table->bigInteger('file_size');
            $table->json('metadata')->nullable(); // EXIF data, GPS coordinates, etc.
            $table->timestamp('captured_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('crime_evidence');
    }
};
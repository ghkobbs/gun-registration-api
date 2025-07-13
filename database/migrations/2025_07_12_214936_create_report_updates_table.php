<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('report_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crime_report_id')->constrained()->cascadeOnDelete();
            $table->foreignId('updated_by')->constrained('users');
            $table->string('update_type'); // status_change, comment, assignment, etc.
            $table->text('message');
            $table->enum('visibility', ['public', 'internal', 'reporter_only'])->default('public');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('report_updates');
    }
};
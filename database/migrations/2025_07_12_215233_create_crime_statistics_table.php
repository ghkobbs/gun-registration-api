<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('crime_statistics', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->foreignId('crime_type_id')->constrained()->cascadeOnDelete();
            $table->foreignId('region_id')->nullable()->constrained();
            $table->foreignId('district_id')->nullable()->constrained();
            $table->foreignId('community_id')->nullable()->constrained();
            $table->integer('total_reports')->default(0);
            $table->integer('resolved_reports')->default(0);
            $table->integer('pending_reports')->default(0);
            $table->decimal('resolution_rate', 5, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('crime_statistics');
    }
};
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('crime_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crime_category_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description');
            $table->integer('severity_level')->default(1); // 1=low, 2=medium, 3=high, 4=critical
            $table->boolean('requires_immediate_response')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('crime_types');
    }
};
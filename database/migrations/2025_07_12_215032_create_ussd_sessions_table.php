<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('ussd_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->unique();
            $table->string('phone_number');
            $table->string('current_menu');
            $table->json('session_data');
            $table->string('language')->default('en');
            $table->enum('status', ['active', 'completed', 'timeout', 'cancelled'])->default('active');
            $table->timestamp('last_activity');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('ussd_sessions');
    }
};
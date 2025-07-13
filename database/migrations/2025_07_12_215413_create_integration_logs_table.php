<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('integration_logs', function (Blueprint $table) {
            $table->id();
            $table->string('service_name'); // nia, police_db, payment_gateway, etc.
            $table->string('operation_type'); // validate, query, update, etc.
            $table->text('request_data');
            $table->text('response_data')->nullable();
            $table->enum('status', ['success', 'failed', 'timeout', 'error'])->default('success');
            $table->text('error_message')->nullable();
            $table->integer('response_time')->nullable(); // in milliseconds
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('integration_logs');
    }
};
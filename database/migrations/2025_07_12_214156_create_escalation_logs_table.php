<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('escalation_logs', function (Blueprint $table) {
            $table->id();
            $table->morphs('escalatable'); // gun_applications, etc.
            $table->foreignId('escalation_rule_id')->constrained()->cascadeOnDelete();
            $table->foreignId('escalated_by')->nullable()->constrained('users');
            $table->foreignId('escalated_to')->nullable()->constrained('users');
            $table->text('escalation_reason');
            $table->enum('status', ['pending', 'acknowledged', 'resolved'])->default('pending');
            $table->timestamp('escalated_at')->useCurrent();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('escalation_logs');
    }
};
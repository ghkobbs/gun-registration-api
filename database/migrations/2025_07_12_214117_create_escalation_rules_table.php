<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('escalation_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description');
            $table->string('trigger_condition'); // e.g., 'days_since_submission'
            $table->integer('threshold_value'); // e.g., 7 days
            $table->string('escalation_action'); // e.g., 'notify_supervisor'
            $table->json('escalation_targets'); // user IDs or roles to escalate to
            $table->integer('priority_level')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('escalation_rules');
    }
};
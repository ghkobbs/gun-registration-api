<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('gun_applications', function (Blueprint $table) {
            $table->id();
            $table->string('application_number')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('application_type', ['new_registration', 'renewal', 'transfer', 'modification']);
            $table->enum('status', [
                'draft', 'submitted', 'under_review', 'documents_required',
                'approved', 'rejected', 'expired', 'escalated'
            ])->default('draft');
            $table->text('purpose_of_ownership');
            $table->boolean('has_previous_conviction')->default(false);
            $table->text('conviction_details')->nullable();
            $table->boolean('has_mental_health_issues')->default(false);
            $table->text('mental_health_details')->nullable();
            $table->json('emergency_contacts');
            $table->text('rejection_reason')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_escalated')->default(false);
            $table->timestamp('escalated_at')->nullable();
            $table->foreignId('escalated_by')->nullable()->constrained('users');
            $table->text('escalation_reason')->nullable();
            $table->integer('priority_level')->default(1); // 1=low, 2=medium, 3=high, 4=urgent
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('gun_applications');
    }
};
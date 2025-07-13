<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('crime_reports', function (Blueprint $table) {
            $table->id();
            $table->string('report_number')->unique();
            $table->string('reference_code')->unique(); // for USSD follow-up
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('crime_type_id')->constrained()->cascadeOnDelete();
            $table->string('reporter_name')->nullable();
            $table->string('reporter_phone')->nullable();
            $table->string('reporter_email')->nullable();
            $table->boolean('is_anonymous')->default(false);
            $table->enum('reporting_method', ['app', 'ussd', 'web', 'phone', 'in_person'])->default('app');
            $table->text('incident_description');
            $table->timestamp('incident_date');
            $table->string('incident_location');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->foreignId('region_id')->nullable()->constrained();
            $table->foreignId('district_id')->nullable()->constrained();
            $table->foreignId('community_id')->nullable()->constrained();
            $table->integer('suspects_count')->default(0);
            $table->integer('victims_count')->default(0);
            $table->integer('witnesses_count')->default(0);
            $table->enum('urgency_level', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->enum('status', [
                'submitted', 'under_investigation', 'assigned', 'in_progress',
                'resolved', 'closed', 'cancelled'
            ])->default('submitted');
            $table->text('additional_notes')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users');
            $table->timestamp('assigned_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users');
            $table->timestamp('closed_at')->nullable();
            $table->text('closure_reason')->nullable();
            $table->string('preferred_language', 10)->default('en');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('crime_reports');
    }
};
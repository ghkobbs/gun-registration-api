<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('gun_registrations', function (Blueprint $table) {
            $table->id();
            $table->string('registration_number')->unique();
            $table->foreignId('application_id')->constrained('gun_applications')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('firearm_type'); // pistol, rifle, shotgun, etc.
            $table->string('make');
            $table->string('model');
            $table->string('caliber');
            $table->string('serial_number')->unique();
            $table->year('manufacture_year');
            $table->string('barrel_length')->nullable();
            $table->string('overall_length')->nullable();
            $table->decimal('weight', 8, 2)->nullable();
            $table->text('additional_features')->nullable();
            $table->string('acquisition_method'); // purchased, inherited, transferred, etc.
            $table->string('previous_owner_name')->nullable();
            $table->string('previous_owner_id')->nullable();
            $table->date('acquisition_date');
            $table->decimal('purchase_price', 10, 2)->nullable();
            $table->string('dealer_name')->nullable();
            $table->string('dealer_license_number')->nullable();
            $table->enum('status', ['active', 'suspended', 'revoked', 'expired'])->default('active');
            $table->date('registration_date');
            $table->date('expiry_date');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('gun_registrations');
    }
};
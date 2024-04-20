<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->string("first_name");
            $table->string("last_name");
            $table->string("phone");
            $table->string("gender");
            $table->string("marital_status");
            $table->string("religion");
            $table->string("preferred_language");
            $table->string("nationality");
            $table->string("lga");
            $table->string("town");
            $table->string("card_number")->nullable();
            $table->string("next_of_kin")->nullable();
            $table->string("next_of_kin_phone")->nullable();
            $table->string("nature_of_relationship")->nullable();
            $table->string("date_of_birth")->nullable();
            $table->string("insurance_number")->nullable();
            $table->string("ward")->nullable();
            $table->string("state_of_residence");
            $table->text("address_of_residence");
            $table->string("patient_id")->nullable();

            $table->unsignedBigInteger("user_id");
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};

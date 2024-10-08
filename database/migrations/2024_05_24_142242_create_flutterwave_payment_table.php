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
        Schema::create('flutterwave_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("user_id");
            $table->unsignedBigInteger("patient_id");
            $table->integer("account_id");
            $table->decimal("amount");
            $table->decimal("amount_settled");
            $table->decimal("app_fee");
            $table->decimal("charged_amount");
            $table->string("country");
            $table->string("expiry");
            $table->string("first_6digits");
            $table->string("issuer");
            $table->string("last_4digits");
            $table->string("card_token");
            $table->string("card_type");
            $table->string("email");
            $table->string("name");
            $table->string("flw_ref");
            $table->integer("phone_number");
            $table->string("ip");
            $table->string("processor_response");
            $table->string("status");
            $table->string("narration");
            $table->decimal("merchant_fee");
            $table->string("tx_ref");
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('patient_id')->references('id')->on('patients')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('flutterwave_payments');
    }
};

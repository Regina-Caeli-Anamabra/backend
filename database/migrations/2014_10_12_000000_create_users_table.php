<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username');
            $table->string('email', 100)->nullable();
            $table->string('phone', 12);
            $table->string('created_by', 100);
            $table->string('last_modified_by', 100);
            $table->enum('authentication_type', ["EMAIL", "SMS"]);
            $table->string('password');
            $table->integer('vCode');
            $table->tinyInteger('verified')->default(0);
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};

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
        Schema::create('Workers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('totalDia');
            $table->timestamps();
        });

        Schema::table('Sales', function (Blueprint $table) {
            $table->unsignedBigInteger('worker_id')->nullable();
            $table->foreign('worker_id')->references('id')->on('Workers')->onDelete('cascade');
        });
        Schema::table('Schedules', function (Blueprint $table) {
            $table->unsignedBigInteger('worker_id');
            $table->foreign('worker_id')->references('id')->on('Workers')->onDelete('cascade');
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('Workers');
    }
};

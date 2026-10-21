<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('Workers')) {
            Schema::table('Workers', function (Blueprint $table) {
                if (!Schema::hasColumn('Workers', 'commission_percentage')) {
                    $table->integer('commission_percentage')->default(50)->after('totalDia');
                }
            });
        }

        if (!Schema::hasTable('WorkerSettlements')) {
            Schema::create('WorkerSettlements', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('worker_id')->nullable();
                $table->string('worker_name');
                $table->integer('gross_amount')->default(0);
                $table->integer('commission_percentage')->default(50);
                $table->integer('paid_amount')->default(0);
                $table->integer('workshop_share')->default(0);
                $table->date('settlement_date')->nullable();
                $table->string('notes')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('Workers') && Schema::hasColumn('Workers', 'commission_percentage')) {
            Schema::table('Workers', function (Blueprint $table) {
                $table->dropColumn('commission_percentage');
            });
        }

        Schema::dropIfExists('WorkerSettlements');
    }
};

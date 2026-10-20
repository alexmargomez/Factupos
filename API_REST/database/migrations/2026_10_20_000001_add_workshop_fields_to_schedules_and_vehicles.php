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
        if (Schema::hasTable('Schedules')) {
            Schema::table('Schedules', function (Blueprint $table) {
                if (!Schema::hasColumn('Schedules', 'workshop_status')) {
                    $table->string('workshop_status', 50)->default('Recibido')->after('state');
                }
                if (!Schema::hasColumn('Schedules', 'mileage')) {
                    $table->unsignedInteger('mileage')->nullable()->after('workshop_status');
                }
                if (!Schema::hasColumn('Schedules', 'notes')) {
                    $table->text('notes')->nullable()->after('mileage');
                }
            });
        }

        if (Schema::hasTable('Vehicles')) {
            Schema::table('Vehicles', function (Blueprint $table) {
                if (!Schema::hasColumn('Vehicles', 'current_mileage')) {
                    $table->unsignedInteger('current_mileage')->nullable()->after('customer_id');
                }
                if (!Schema::hasColumn('Vehicles', 'next_oil_change_km')) {
                    $table->unsignedInteger('next_oil_change_km')->nullable()->after('current_mileage');
                }
                if (!Schema::hasColumn('Vehicles', 'soat_due_date')) {
                    $table->date('soat_due_date')->nullable()->after('next_oil_change_km');
                }
                if (!Schema::hasColumn('Vehicles', 'tecno_due_date')) {
                    $table->date('tecno_due_date')->nullable()->after('soat_due_date');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('Schedules')) {
            Schema::table('Schedules', function (Blueprint $table) {
                $cols = [];
                foreach (['workshop_status', 'mileage', 'notes'] as $col) {
                    if (Schema::hasColumn('Schedules', $col)) {
                        $cols[] = $col;
                    }
                }
                if (!empty($cols)) {
                    $table->dropColumn($cols);
                }
            });
        }

        if (Schema::hasTable('Vehicles')) {
            Schema::table('Vehicles', function (Blueprint $table) {
                $cols = [];
                foreach (['current_mileage', 'next_oil_change_km', 'soat_due_date', 'tecno_due_date'] as $col) {
                    if (Schema::hasColumn('Vehicles', $col)) {
                        $cols[] = $col;
                    }
                }
                if (!empty($cols)) {
                    $table->dropColumn($cols);
                }
            });
        }
    }
};

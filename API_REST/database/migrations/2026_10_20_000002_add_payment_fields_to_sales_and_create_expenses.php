<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('Sales')) {
            Schema::table('Sales', function (Blueprint $table) {
                if (!Schema::hasColumn('Sales', 'payment_status')) {
                    $table->string('payment_status')->default('Pagado')->after('payment_method');
                }
                if (!Schema::hasColumn('Sales', 'paid_amount')) {
                    $table->integer('paid_amount')->nullable()->after('payment_status');
                }
                if (!Schema::hasColumn('Sales', 'pending_balance')) {
                    $table->integer('pending_balance')->default(0)->after('paid_amount');
                }
                if (!Schema::hasColumn('Sales', 'payment_notes')) {
                    $table->string('payment_notes')->nullable()->after('pending_balance');
                }
            });
        }

        if (!Schema::hasTable('Expenses')) {
            Schema::create('Expenses', function (Blueprint $table) {
                $table->increments('id');
                $table->string('description');
                $table->string('category')->default('General');
                $table->integer('amount');
                $table->string('payment_method')->default('Efectivo');
                $table->date('expense_date')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('Sales')) {
            Schema::table('Sales', function (Blueprint $table) {
                $cols = [];
                foreach (['payment_status', 'paid_amount', 'pending_balance', 'payment_notes'] as $col) {
                    if (Schema::hasColumn('Sales', $col)) {
                        $cols[] = $col;
                    }
                }
                if (!empty($cols)) {
                    $table->dropColumn($cols);
                }
            });
        }

        Schema::dropIfExists('Expenses');
    }
};

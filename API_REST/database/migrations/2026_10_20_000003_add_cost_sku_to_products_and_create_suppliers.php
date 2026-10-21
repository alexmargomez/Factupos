<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('Suppliers')) {
            Schema::create('Suppliers', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name');
                $table->string('nit')->nullable();
                $table->string('phone')->nullable();
                $table->string('email')->nullable();
                $table->string('contact_name')->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('Products')) {
            Schema::table('Products', function (Blueprint $table) {
                if (!Schema::hasColumn('Products', 'sku')) {
                    $table->string('sku')->nullable()->after('id');
                }
                if (!Schema::hasColumn('Products', 'cost_price')) {
                    $table->integer('cost_price')->default(0)->after('name');
                }
                if (!Schema::hasColumn('Products', 'min_stock')) {
                    $table->integer('min_stock')->default(5)->after('price');
                }
                if (!Schema::hasColumn('Products', 'supplier_id')) {
                    $table->unsignedInteger('supplier_id')->nullable()->after('category_id');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('Products')) {
            Schema::table('Products', function (Blueprint $table) {
                $cols = [];
                foreach (['sku', 'cost_price', 'min_stock', 'supplier_id'] as $col) {
                    if (Schema::hasColumn('Products', $col)) {
                        $cols[] = $col;
                    }
                }
                if (!empty($cols)) {
                    $table->dropColumn($cols);
                }
            });
        }

        Schema::dropIfExists('Suppliers');
    }
};

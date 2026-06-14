<?php

use App\Enums\Client\ServiceDiscountCategory;
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
        Schema::table('client_service_discounts', function (Blueprint $table) {
            $table->boolean('category')->default(ServiceDiscountCategory::DISCOUNT->value);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('client_service_discounts', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }
};

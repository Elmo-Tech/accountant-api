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
        Schema::table('invoices', function (Blueprint $table) {
            $table->tinyInteger('discount_type')->default(0)->after('payment_type_id');
            $table->decimal('discount_amount', 10, 2)->nullable()->after('discount_type'); // Pricing value
            $table->foreignId('bank_account_id')->nullable()->constrained('parameter_values')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_categories', function (Blueprint $table) {
            //
        });
    }
};

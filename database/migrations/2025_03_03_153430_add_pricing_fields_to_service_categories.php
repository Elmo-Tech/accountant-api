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
        Schema::table('service_categories', function (Blueprint $table) {
            $table->boolean('extra_is_pricable')->default(false)->after('add_to_invoice'); // Determines if pricing is applicable
            $table->string('extra_code')->nullable()->after('extra_is_pricable'); // Unique service category code
            $table->string('extra_price_description')->nullable()->after('extra_code'); // Description of pricing details
            $table->decimal('extra_price', 10, 2)->nullable()->after('extra_price_description'); // Pricing value
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

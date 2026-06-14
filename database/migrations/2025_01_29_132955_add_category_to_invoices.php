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
        Schema::table('invoices', function (Blueprint $table) {
            $table->date('end_at')->nullable();
            $table->foreignId('payment_type_id')->nullable()->constrained('parameter_values')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('end_at');
            $table->dropForeign('payment_type_id');
            $table->dropColumn('payment_type_id');
        });
    }
};

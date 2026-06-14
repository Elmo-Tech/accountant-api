<?php

use App\Enums\Client\AddableToBulk;
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
        Schema::table('clients', function (Blueprint $table) {
            $table->boolean('addable_to_bulk_invoice')->default(AddableToBulk::ADDABLE->value);
            $table->smallInteger('allowed_days_to_pay')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('addable_to_bulk_invoice');
            $table->dropColumn('allowed_days_to_pay');
        });
    }
};

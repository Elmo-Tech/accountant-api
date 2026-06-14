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
        Schema::table('clients', function (Blueprint $table) {
            $table->boolean('is_company')->default(false)->after('allowed_days_to_pay'); // Change 'some_column' to an actual column name
            $table->decimal('total_tax', 10, 2)->default(0)->after('is_company');
            $table->string('total_tax_description')->nullable()->after('total_tax'); // Change 'some_column' to an actual column name
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('is_company');
            $table->dropColumn('total_tax');
            $table->dropColumn('total_tax_description');
        });
    }
};

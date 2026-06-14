<?php

use App\Traits\CreatedUpdatedByMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use CreatedUpdatedByMigration;
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('client_pay_installments', function (Blueprint $table) {
            $table->id();
            $table->date('start_at')->nullable();
            $table->date('end_at')->nullable();
            $table->decimal('amount', 10, 2)->nullable();
            $table->unsignedBigInteger('parameter_value_id')->nullable();
            $table->foreign('parameter_value_id')->references('id')->on('parameter_values')->nullOnDelete();
            $table->unsignedBigInteger('client_id')->nullable();
            $table->foreign('client_id')->references('id')->on('clients')->nullOnDelete();
            $this->CreatedUpdatedByRelationship($table);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_pay_installments');
    }
};

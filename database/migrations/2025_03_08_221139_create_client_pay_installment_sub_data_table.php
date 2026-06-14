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
        Schema::create('client_pay_installment_sub_data', function (Blueprint $table) {
            $table->id();
            $table->decimal('price', 10, 2);
            $table->unsignedBigInteger('parameter_value_id')->nullable();
            $table->foreign('parameter_value_id')->references('id')->on('parameter_values')->nullOnDelete();
            $table->unsignedBigInteger('client_pay_installment_id')->nullable();
            $table->foreign('client_pay_installment_id', 'fk_sub_data_installment')
            ->references('id')->on('client_pay_installments')
            ->onDelete('set null');
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
        Schema::dropIfExists('client_pay_installment_sub_data');
    }
};

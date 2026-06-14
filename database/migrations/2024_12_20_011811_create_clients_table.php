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
        //payment_type_id , pay_steps_id, payment_type_two_id, iban, abi, cab
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_type_id')->nullable()->constrained('parameter_values')->onDelete('cascade');
            $table->foreignId('pay_steps_id')->nullable()->constrained('parameter_values')->onDelete('cascade');
            $table->foreignId('payment_type_two_id')->nullable()->constrained('parameter_values')->onDelete('cascade');
            $table->string('iban')->nullable();
            $table->string('abi')->nullable();
            $table->string('cab')->nullable();
            $table->string('iva', 60)->nullable();
            $table->string('ragione_sociale')->nullable();
            $table->string('cf', 60)->nullable();
            $table->text('note')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('hours_per_month', 20)->nullable();
            $table->decimal('price', 8, 2)->nullable();
            $this->CreatedUpdatedByRelationship($table);
            $table->timestamps();
            $table->softDeletes();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');

    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('client_contacts', function (Blueprint $table) {
            $table->string('cf')->nullable();
        });

        // Schema::table('client_pay_installment_sub_data', function (Blueprint $table) {
        //     $table->unsignedBigInteger('payment_type_id')->nullable();
        //     $table->foreign('payment_type_id')
        //         ->references('id')
        //         ->on('parameter_values')
        //         ->nullOnDelete();
        // });
    }

    public function down()
    {
        Schema::table('client_contacts', function (Blueprint $table) {
            $table->string('cf')->nullable();
        });

        // Schema::table('client_pay_installment_sub_data', function (Blueprint $table) {
        //     $table->dropForeign(['payment_type_id']);
        //     $table->dropColumn('payment_type_id');
        // });
    }
};

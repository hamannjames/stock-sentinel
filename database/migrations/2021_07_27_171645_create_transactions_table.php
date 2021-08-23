<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->date('transaction_date');
            $table->foreignId('transactor_id')->constrained()->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('ticker_id')->constrained()->onDelete('cascade')->onUpdate('cascade');
            $table->mediumInteger('ticker_received_id')->nullable();
            $table->foreignId('transactor_type_id')->constrained()->onUpdate('cascade');
            $table->string('transaction_owner', 30);
            $table->foreignId('transaction_type_id')->constrained()->onUpdate('cascade');
            $table->foreignId('transaction_asset_type_id')->constrained()->onUpdate('cascade');;
            $table->unsignedInteger('transaction_amount_min')->unsigned()->nullable();
            $table->unsignedInteger('transaction_amount_max')->unsigned()->nullable();
            $table->unsignedInteger('transaction_amount_exact')->unsigned()->nullable();
            $table->unique(['ptr_id', 'ptr_row']);
            $table->string('ptr_id', 100);
            $table->smallInteger('ptr_row');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transactions');
    }
}

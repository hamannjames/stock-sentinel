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
            $table->foreignId('transactor_type_id')->constrained()->onUpdate('cascade');
            $table->string('transaction_owner', 30);
            $table->foreignId('transaction_type_id')->constrained()->onUpdate('cascade');
            $table->foreignId('transaction_asset_type_id')->constrained()->onUpdate('cascade');;
            $table->mediumInteger('transaction_amount_min')->unsigned()->nullable();
            $table->mediumInteger('transaction_amount_max')->unsigned()->nullable();
            $table->mediumInteger('transaction_amount_exact')->unsigned()->nullable();
            $table->foreignId('ptr_id')->constrained()->onDelete('cascade')->onUpdate('cascade');
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

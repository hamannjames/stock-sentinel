<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transactors', function (Blueprint $table) {
            $table->id();
            $table->string('pro_publica_id', 30)->unique();
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->foreignId('transactor_type_id')->constrained()->onUpdate('cascade');
            $table->boolean('in_office');
            $table->string('party', 5);
            $table->string('gender', 5)->nullable();
            $table->char('state', 2);
            $table->integer('congress');
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
        Schema::dropIfExists('transactors');
    }
}

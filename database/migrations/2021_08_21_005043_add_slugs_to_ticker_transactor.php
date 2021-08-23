<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSlugsToTickerTransactor extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tickers', function (Blueprint $table) {
            $table->string('slug', 30)->nullable();
        });

        Schema::table('transactors', function (Blueprint $table) {
            $table->string('slug', 30)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tickers', function (Blueprint $table) {
            $table->dropColumn('slug');
        });

        Schema::table('transactors', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
    }
}

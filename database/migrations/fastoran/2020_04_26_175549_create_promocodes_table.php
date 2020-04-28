<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePromocodesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('promocodes', function (Blueprint $table) {
            $table->increments('id');
            $table->string('id')->nullable();
            $table->boolean('active')->default(true);
            $table->unsignedInteger('user_id')->nullable();
            $table->unsignedInteger('promotion_id');

            if (env("DB_CONNECTION") == 'mysql') {
                $table->foreign('promotion_id')->references('id')->on('promotions');
            }

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
        Schema::dropIfExists('promocodes');
    }
}

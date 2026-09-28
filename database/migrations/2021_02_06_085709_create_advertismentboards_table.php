<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateAdvertismentboardsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return null
     */
    public function up()
    {
        Schema::create('advertismentboards', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('image_id')->unsigned()->nullable();
            $table->json('title');
            $table->json('slug');
            $table->json('summary');
            $table->json('body');
            $table->integer('category_id')->unsigned()->nullable()->default(null);
            $table->string('author')->nullable();
            $table->date('date')->nullable();
            $table->string('tag')->nullable();
            $table->json('status');
            $table->timestamps();

            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return null
     */
    public function down()
    {
        Schema::drop('advertismentboards');
    }
}

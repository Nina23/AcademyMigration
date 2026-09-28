<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateBiographiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return null
     */
    public function up()
    {
        Schema::create('biographies', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('image_id')->unsigned()->nullable();
            $table->json('status');
            $table->json('name')->nullable()->default(null);
            $table->json('title');
            $table->json('slug');
            $table->json('section')->nullable()->default(null);
            $table->json('summary')->nullable()->default(null);
            $table->integer('category_id')->unsigned()->nullable()->default(null);
            $table->string('email')->nullable()->default(null);
            $table->string('phone')->nullable()->default(null);
            $table->string('external_link')->nullable()->default(null);
            $table->string('linkedin')->nullable()->default(null);
            $table->string('instagram')->nullable()->default(null);
            $table->string('imdb')->nullable()->default(null);
            $table->json('awards')->nullable()->default(null);
            $table->json('publications')->nullable()->default(null);
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
        Schema::drop('biographies');
    }
}

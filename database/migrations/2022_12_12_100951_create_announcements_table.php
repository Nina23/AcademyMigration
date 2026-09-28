<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateAnnouncementsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return null
     */
    public function up()
    {
        Schema::create('announcements', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('image_id')->unsigned()->nullable();
            $table->json('status');
            $table->json('title');
            $table->json('slug');
            $table->json('summary');
            $table->json('body');
            $table->integer('announcement_category_id')->unsigned()->nullable()->default(null);
            $table->json('program');
            $table->integer('year')->default(1);
            $table->json('professor');
            $table->json('location');
            $table->dateTime('date');
            $table->dateTime('expiry');
            $table->json('highlight');
            $table->timestamps();

            $table->foreign('announcement_category_id')->references('id')->on('announcement_categories')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return null
     */
    public function down()
    {
        Schema::drop('announcements');
    }
}

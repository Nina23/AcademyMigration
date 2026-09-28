<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClassschedulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return null
     */
    public function up()
    {
        Schema::create('classschedules', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('image_id')->unsigned()->nullable();
            $table->json('status');
            $table->json('title');
            $table->json('slug');
            $table->json('summary');
            $table->json('body');
            $table->integer('announcement_category_id')->unsigned()->nullable()->default(null);
            $table->integer('announcement_department_id')->unsigned()->nullable()->default(null);
            $table->integer('year')->default(1);
            $table->integer('day_id')->default(1);
            $table->dateTime('from_date');
            $table->dateTime('to_date');
            $table->json('professor');
            $table->json('location');
            $table->dateTime('expiry');
            $table->integer('position')->default(1);
            $table->timestamps();

            $table->foreign('announcement_category_id')->references('id')->on('announcement_categories')->onDelete('cascade');
            $table->foreign('announcement_department_id')->references('id')->on('announcement_departments')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return null
     */
    public function down()
    {
        Schema::drop('classschedules');
    }
}

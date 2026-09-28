<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClassScheduleItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('class_schedule_items', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('class_schedule_id')->unsigned()->nullable()->default(null);
            $table->json('title');
            $table->integer('day_id')->default(1);
            $table->integer('type')->default(1);
            $table->dateTime('from_date');
            $table->dateTime('to_date');
            $table->json('professor');
            $table->json('location');
            $table->integer('position')->default(1);
            $table->timestamps();

            $table->foreign('class_schedule_id')->references('id')->on('classschedules')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('class_schedule_items');
    }
}

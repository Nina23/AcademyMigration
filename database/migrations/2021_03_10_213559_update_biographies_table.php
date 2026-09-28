<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateBiographiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('biographies', function (Blueprint $table) {
            $table->json('directory_departments')->nullable()->after('status');
            $table->json('chef_departments')->nullable()->after('status');
            $table->dropForeign(['category_id']);
            $table->dropColumn('category_id');
            $table->integer('department_id')->unsigned()->nullable()->default(null)->after('status');
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('biographies');
    }
}

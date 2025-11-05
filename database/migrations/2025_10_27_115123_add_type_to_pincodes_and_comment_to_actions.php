<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('pincodes', function (Blueprint $table) {
            $table->enum('type', ['single', 'multi'])->default('single')->after('license_id');
        });

        Schema::table('actions', function (Blueprint $table) {
            $table->text('comment')->nullable()->after('device_information');
        });

        // Делаем max_count nullable для лицензий
        Schema::table('licenses', function (Blueprint $table) {
            $table->integer('max_count')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('pincodes', function (Blueprint $table) {
            $table->dropColumn('type');
        });

        Schema::table('actions', function (Blueprint $table) {
            $table->dropColumn('comment');
        });

        Schema::table('licenses', function (Blueprint $table) {
            $table->integer('max_count')->nullable(false)->change();
        });
    }
};
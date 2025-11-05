php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('actions', function (Blueprint $table) {
            $table->binary('file_data')->nullable();
            $table->string('file_name')->nullable();
        });
    }

    public function down()
    {
        Schema::table('actions', function (Blueprint $table) {
            $table->dropColumn(['file_data', 'file_name']);
        });
    }
};

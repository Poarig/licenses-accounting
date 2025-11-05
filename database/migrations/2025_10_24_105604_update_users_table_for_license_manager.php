<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // Удаляем стандартные поля Laravel, которые нам не нужны
            $table->dropColumn('email');
            $table->dropColumn('email_verified_at');
            
            // Добавляем нужные поля
            
            // Переименовываем поле password для совместимости (если нужно)
            // Поле password уже существует по умолчанию
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            // Возвращаем обратно при откате миграции
            $table->string('email')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            
            $table->dropColumn(['surname', 'patronymic', 'login']);
        });
    }
};
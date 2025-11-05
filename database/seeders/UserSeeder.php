<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {
        User::create([
            'name' => 'Администратор',
            'surname' => 'Системы',
            'patronymic' => '',
            'login' => 'admin',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
        ]);

        // Дополнительные тестовые пользователи
        User::create([
            'name' => 'Иван',
            'surname' => 'Петров',
            'patronymic' => 'Сергеевич',
            'login' => 'ipetrov',
            'password' => Hash::make('password123'),
            'role' => 'user',
        ]);
    }
}
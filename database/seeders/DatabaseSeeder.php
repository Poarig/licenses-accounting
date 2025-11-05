<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;

class DatabaseSeeder extends Seeder
{
    public function run()
    {   
        
        $this->call([
            UserSeeder::class,
        ]);

        Product::create(['name' => '1С:Бухгалтерия', 'description' => 'Программа для бухгалтерского учета']);
        Product::create(['name' => '1С:Зарплата и управление персоналом', 'description' => 'Программа для расчета зарплаты']);
        Product::create(['name' => '1С:Торговля', 'description' => 'Программа для управления торговлей']);
        Product::create(['name' => '1С:Розница', 'description' => 'Программа для розничной торговли']);
        Product::create(['name' => '1С:Управление нашей фирмой', 'description' => 'Программа для управления бизнесом']);
    }

    
}